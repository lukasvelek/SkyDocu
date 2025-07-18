<?php

namespace App\Modules\SuperAdminSettingsModule;

use App\Constants\AppDesignThemes;
use App\Constants\DateFormats;
use App\Constants\TimeFormats;
use App\Core\DB\DatabaseRow;
use App\Core\FileManager;
use App\Core\FileUploadManager;
use App\Core\HashManager;
use App\Core\Http\FormRequest;
use App\Core\Http\HttpRequest;
use App\Exceptions\AException;
use App\Exceptions\GeneralException;
use App\Exceptions\RequiredAttributeIsNotSetException;
use App\Helpers\DateTimeFormatHelper;
use App\Helpers\GridHelper;
use App\Helpers\UserHelper;
use App\UI\GridBuilder2\Row;
use App\UI\HTML\HTML;
use App\UI\LinkBuilder;

class UsersPresenter extends ASuperAdminSettingsPresenter {
    public function __construct() {
        parent::__construct('UsersPresenter', 'Users');
    }

    public function renderList() {
        $this->template->links = [
            LinkBuilder::createSimpleLink('New user', $this->createURL('newUserForm'), 'link')
        ];
    }

    protected function createComponentUsersGrid(HttpRequest $request) {
        $grid = $this->componentFactory->getGridBuilder();

        $qb = $this->app->userRepository->composeQueryForUsers();

        $grid->createDataSourceFromQueryBuilder($qb, 'userId');
        $grid->setGridName(GridHelper::GRID_USERS);

        $grid->addColumnText('fullname', 'Full name');
        $grid->addColumnText('username', 'Username');

        $profile = $grid->addAction('profile');
        $profile->setTitle('Profile');
        $profile->onCanRender[] = function(DatabaseRow $row, Row $_row) {
            if($row->username == 'service_user') {
                return false;
            } else {
                return true;
            }
        };
        $profile->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
            $el = HTML::el('a')
                ->text('Profile')
                ->class('grid-link')
                ->href($this->createFullURLString('SuperAdminSettings:Users', 'profile', ['userId' => $primaryKey]));

            return $el;
        };

        $edit = $grid->addAction('edit');
        $edit->setTitle('Edit');
        $edit->onCanRender[] = function(DatabaseRow $row, Row $_row) {
            if($row->username == 'service_user') {
                return false;
            } else {
                return true;
            }
        };
        $edit->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
            $el = HTML::el('a')
                ->text('Edit')
                ->class('grid-link')
                ->href($this->createFullURLString('SuperAdminSettings:Users', 'editUserForm', ['userId' => $primaryKey]));

            return $el;
        };

        $delete = $grid->addAction('delete');
        $delete->setTitle('Delete');
        $delete->onCanRender[] = function(DatabaseRow $row, Row $_row) {
            if(!in_array($row->username, ['admin', 'service_user', $this->getUser()->getUsername()])) {
                return true;
            } else {
                return false;
            }
        };
        $delete->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
            $el = HTML::el('a')
                ->text('Delete')
                ->class('grid-link')
                ->href($this->createFullURLString('SuperAdminSettings:Users', 'deleteUserForm', ['userId' => $primaryKey]));

            return $el;
        };

        return $grid;
    }

    public function handleNewUserForm(?FormRequest $fr = null) {
        if($fr !== null) {
            try {
                if($fr->password != $fr->password2) {
                    throw new GeneralException('Passwords do not match.', null, false);
                }

                $ok = true;
                try {
                    $user = $this->app->userManager->getUserByUsername($fr->username, false);
                    $ok = false;
                } catch(AException $e) {
                    $ok = true;
                }

                if($ok === false) {
                    throw new GeneralException('User with this username already exists.', null, false);
                }

                $this->app->userRepository->beginTransaction(__METHOD__);

                $email = null;
                if($fr->isset('email') && $fr->email !== null) {
                    $email = $fr->email;
                }

                $this->app->userManager->createNewUser($fr->username, $fr->fullname, HashManager::hashPassword($fr->password), $email);

                $this->app->userRepository->commit($this->getUserId(), __METHOD__);

                $this->flashMessage('User successfully created.', 'success');
            } catch(AException $e) {
                $this->app->userRepository->rollback(__METHOD__);

                $this->flashMessage('Could not create user. Reason: ' . $e->getMessage(), 'error', 10);
            }

            $this->redirect($this->createURL('list'));
        }
    }

    public function renderNewUserForm() {
        $this->template->links = $this->createBackUrl('list');
    }

    protected function createComponentNewUserForm(HttpRequest $request) {
        $form = $this->componentFactory->getFormBuilder();

        $form->setAction($this->createURL('newUserForm'));

        $form->addTextInput('username', 'Username:')
            ->setRequired();

        $form->addTextInput('fullname', 'Fullname:')
            ->setRequired();

        $form->addEmailInput('email', 'Email:');

        $form->addPasswordInput('password', 'Password:')
            ->setRequired();

        $form->addPasswordInput('password2', 'Password again:')
            ->setRequired();

        $form->addSubmit('Add');

        return $form;
    }

    public function renderProfile() {
        $userId = $this->httpRequest->get('userId');
        if($userId === null) {
            throw new RequiredAttributeIsNotSetException('userId');
        }

        $force = false;
        if($this->httpRequest->get('force') == 1) {
            $force = true;
        }

        try {
            $user = $this->app->userManager->getUserById($userId, $force);
        } catch(AException $e) {
            $this->flashMessage('This user does not exist.', 'error', 10);
            $this->redirect($this->createURL('list'));
        }

        $userProfile = [];

        $addInfo = function(string $title, string $data) use (&$userProfile) {
            $userProfile[] = '<span id="row-' . count($userProfile) . '"><p><b>' . $title . ':</b> ' . $data . '</p></span>';
        };

        $memberSince = DateTimeFormatHelper::formatDateToUserFriendly($user->getDateCreated(), $this->getUser()->getDatetimeFormat());

        $addInfo('ID', $user->getId());
        $addInfo('Full name', $user->getFullname());
        $addInfo('Email', $user->getEmail() ?? '-');
        $addInfo('Member since', $memberSince);

        $this->template->user_profile = implode('', $userProfile);
        $this->template->username = $user->getUsername();
        $this->template->links = LinkBuilder::createSimpleLink('&larr; Back', $this->createURL('list'), 'link');

        $profilePictureImageSource = UserHelper::getUserProfilePictureUri(
            $user,
            $this->app->fileStorageManager
        );

        $this->template->user_profile_picture = '
            <img src="' . $profilePictureImageSource . '" width="128px" height="128px" style="border-radius: 100px">
        ';

        $this->template->user_profile_picture_change_link = LinkBuilder::createSimpleLink(
            'Change profile picture',
            $this->createURL(
                'changeProfilePictureForm',
                [
                    'userId' => $userId
                ]
            ),
            'link'
        );
    }

    public function handleEditUserForm(?FormRequest $fr = null) {
        if($fr !== null) {
            $userId = $this->httpRequest->get('userId');
            if($userId === null) {
                throw new RequiredAttributeIsNotSetException('userId');
            }

            try {
                $this->app->userRepository->beginTransaction(__METHOD__);

                $data = [
                    'username' => $fr->username,
                    'fullname' => $fr->fullname,
                    'appDesignTheme' => $fr->appDesignTheme,
                    'dateFormat' => $fr->dateFormat,
                    'timeFormat' => $fr->timeFormat
                ];

                if($fr->isset('email') && $fr->email !== null) {
                    $data['email'] = $fr->email;
                }

                $this->app->userManager->updateUser($userId, $data);

                $this->app->userRepository->commit($this->getUserId(), __METHOD__);

                $this->flashMessage('User updated.', 'success');
            } catch(AException $e) {
                $this->app->userRepository->rollback(__METHOD__);

                $this->flashMessage('Could not update user. Reason: ' . $e->getMessage(), 'error', 10);
            }

            $this->redirect($this->createURL('list'));
        }
    }

    public function renderEditUserForm() {
        $this->template->links = $this->createBackUrl('list');
    }

    protected function createComponentEditUserForm(HttpRequest $request) {
        $user = $this->app->userManager->getUserById($request->get('userId'));

        $themes = [];
        foreach(AppDesignThemes::getAll() as $key => $title) {
            $theme = [
                'value' => $key,
                'text' => $title
            ];

            if($user->getAppDesignTheme() == $key) {
                $theme['selected'] = 'selected';
            }

            $themes[] = $theme;
        }

        $dateFormats = [];
        foreach(DateFormats::FORMATS as $date) {
            $format = [
                'value' => $date,
                'text' => $date
            ];

            if($date == $this->app->currentUser->getDateFormat()) {
                $format['selected'] = 'selected';
            }

            $dateFormats[] = $format;
        }

        $timeFormats = [];
        foreach(TimeFormats::FORMATS as $time) {
            $format = [
                'value' => $time,
                'text' => $time
            ];

            if($time == $this->app->currentUser->getTimeFormat()) {
                $format['selected'] = 'selected';
            }

            $timeFormats[] = $format;
        }
        
        $form = $this->componentFactory->getFormBuilder();

        $form->setAction($this->createURL('editUserForm', ['userId' => $request->get('userId')]));

        $form->addTextInput('username', 'Username:')
            ->setRequired()
            ->setValue($user->getUsername());

        $form->addTextInput('fullname', 'Fullname:')
            ->setRequired()
            ->setValue($user->getFullname());

        $form->addEmailInput('email', 'Email:')
            ->setValue($user->getEmail());

        $form->addSelect('appDesignTheme', 'App theme:')
            ->setRequired()
            ->addRawOptions($themes);

        $form->addSelect('dateFormat', 'Date format:')
            ->setRequired()
            ->addRawOptions($dateFormats);

        $form->addSelect('timeFormat', 'Time format:')
            ->setRequired()
            ->addRawOptions($timeFormats);

        $form->addSubmit('Save');

        return $form;
    }

    public function handleDeleteUserForm(?FormRequest $fr = null) {
        if($fr !== null) {
            try {
                $user = $this->app->userManager->getUserById($this->httpRequest->get('userId'));

                if($user->getUsername() != $fr->username) {
                    throw new GeneralException('Username entered does not match with the username of the user to be deleted.');
                }

                if(!$this->app->userAuth->authUser($fr->password)) {
                    throw new GeneralException('Authentication failed. Bad password entered.');
                }

                $userMemberships = $this->app->groupManager->getMembershipsForUser($user->getId());

                $this->app->userRepository->beginTransaction(__METHOD__);

                // delete user
                $this->app->userManager->deleteUser($user->getId());

                // delete memberships
                foreach($userMemberships as $group) {
                    $this->app->groupManager->removeUserFromGroup($user->getId(), $group->groupId);
                }

                $this->app->userRepository->commit($this->getUserId(), __METHOD__);

                $this->flashMessage('User deleted.', 'success');
            } catch(AException $e) {
                $this->app->userRepository->rollback(__METHOD__);

                $this->flashMessage('Could not delete user. Reason: ' . $e->getMessage(), 'error', 10);
            }

            $this->redirect($this->createURL('list'));
        }
    }

    public function renderDeleteUserForm() {
        $this->template->links = $this->createBackUrl('list');
    }

    protected function createComponentDeleteUserForm(HttpRequest $request) {
        $user = $this->app->userManager->getUserById($request->get('userId'));

        $form = $this->componentFactory->getFormBuilder();

        $form->setAction($this->createURL('deleteUserForm', ['userId' => $request->get('userId')]));

        $form->addLabel('main', 'Do you want to delete user \'' . $user->getUsername() . '\'?');

        $form->addTextInput('username', 'User\'s username:')
            ->setRequired();

        $form->addPasswordInput('password', 'Your password:')
            ->setRequired();
        
        $form->addSubmit('Delete');

        return $form;
    }

    public function renderChangeProfilePictureForm() {}

    protected function createComponentChangeProfilePictureForm() {
        $form = $this->componentFactory->getFormBuilder();

        $form->setAction($this->createURL('changeProfilePictureFormSubmit', ['userId' => $this->httpRequest->get('userId')]));

        $form->addFileInput('profilePictureFile', 'File:')
            ->setRequired();

        $form->addSubmit('Change');

        return $form;
    }

    public function handleChangeProfilePictureFormSubmit(FormRequest $fr) {
        $userId = $this->httpRequest->get('userId');

        try {
            $user = $this->app->userManager->getUserById($userId);

            // upload new picture
            $fum = new FileUploadManager();

            $fileData = $fum->uploadImage($_FILES['profilePictureFile'], $userId, null);

            $this->app->fileStorageRepository->beginTransaction(__METHOD__);

            // delete old picture
            if($user->getProfilePictureFileId() !== null) {
                $file = $this->app->fileStorageManager->getFileById($user->getProfilePictureFileId());

                try {
                    $this->app->fileStorageRepository->beginTransaction(__METHOD__);

                    $this->app->fileStorageManager->deleteFile($user->getProfilePictureFileId());

                    FileManager::deleteFile($file->filepath);

                    $this->app->fileStorageRepository->commit($this->getUserId(), __METHOD__);
                } catch(AException $e) {
                    $this->app->fileStorageRepository->rollback(__METHOD__);

                    $this->logger->error('Could not delete profile picture for user #' . $userId . '. File ID: #' . $user->getProfilePictureFileId() . '. Reason: ' . $e->getMessage(), __METHOD__);
                }
            }

            // create a new database entry for the file
            $fileId = $this->app->fileStorageManager->createNewFile(
                $userId,
                $fileData[FileUploadManager::FILE_FILENAME],
                $fileData[FileUploadManager::FILE_FILEPATH],
                $fileData[FileUploadManager::FILE_FILESIZE]
            );
            
            // update the user with the new profile picture
            $this->app->userManager->updateUser(
                $userId,
                [
                    'profilePictureFileId' => $fileId
                ]
            );

            $this->app->fileStorageRepository->commit($this->getUserId(), __METHOD__);

            $this->flashMessage('Successfully changed profile picture. The change can take few minutes before being visible.', 'success');
        } catch(AException $e) {
            $this->app->fileStorageRepository->rollback(__METHOD__);

            $this->flashMessage('Could not change profile picture. Reason: ' . $e->getMessage(), 'error', 10);
        }

        $this->redirect($this->createURL('profile', ['userId' => $userId, 'force' => '1']));
    }
}

?>