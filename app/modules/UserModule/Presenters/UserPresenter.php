<?php

namespace App\Modules\UserModule;

use App\Components\Static\UserProfileStatic\UserProfileStatic;
use App\Constants\Container\SystemGroups;
use App\Core\Caching\CacheNames;
use App\Core\FileManager;
use App\Core\FileUploadManager;
use App\Core\Http\FormRequest;
use App\Exceptions\AException;
use App\Helpers\DateTimeFormatHelper;
use App\Helpers\LinkHelper;
use App\UI\LinkBuilder;

class UserPresenter extends AUserPresenter {
    public function __construct() {
        parent::__construct('UserPresenter', 'User');
    }

    public function handleProfile() {
        $this->setTitle('User profile');
        
        $userId = $this->httpRequest->get('userId');

        if($userId === null) {
            $this->redirect($this->createURL('profile', ['userId' => $this->getUserId()]));
        }
    }

    public function renderProfile() {
        $userId = $this->httpRequest->get('userId');

        try {
            $user = $this->app->userManager->getUserById($userId);
        } catch(AException $e) {
            $this->flashMessage($e->getMessage(), 'error', 10);
            $this->redirect($this->createFullURL('User:Home', 'dashboard'));
        }

        $userProfile = '';
        $addInfo = function(string $title, string $data) use (&$userProfile) {
            $userProfile .= '<p><b>' . $title . ':</b> ' . $data . '</p>';
        };

        $addInfo('Full name', $user->getFullname());
        $addInfo('Email', ($user->getEmail() ?? '-'));
        $addInfo('Member since', DateTimeFormatHelper::formatDateToUserFriendly($user->getDateCreated(), $this->app->currentUser->getDatetimeFormat()));
        $addInfo('ID', $user->getId());

        $this->template->user_profile = $userProfile;

        $links = [];
        if($userId == $this->getUserId() || $this->groupManager->isUserMemberOfGroupTitle($userId, SystemGroups::ADMINISTRATORS)) {
            $links[] = LinkBuilder::createSimpleLink('Configuration', $this->createFullURL('User:UserConfiguration', 'home', ['userId' => $userId]), 'link');
        }
        $links[] = LinkBuilder::createSimpleLink('Group memberships', $this->createURL('groupMembershipsGrid', ['userId' => $userId]), 'link');

        $this->template->links = LinkHelper::createLinksFromArray($links);
    }

    protected function createComponentUserProfile() {
        $userProfile = new UserProfileStatic(
            $this->httpRequest,
            $this->app->userAbsenceManager,
            $this->app->userSubstituteManager,
            $this->app->userManager
        );

        $userProfile->setApplication($this->app);
        $userProfile->setPresenter($this);

        $userId = $this->httpRequest->get('userId');

        try {
            $user = $this->app->userManager->getUserById($userId);
        } catch(AException $e) {
            $this->flashMessage($e->getMessage(), 'error', 10);
            $this->redirect($this->createFullURL('User:Home', 'dashboard'));
        }

        $userProfile->setUser($user);

        return $userProfile;
    }

    public function renderGroupMembershipsGrid() {
        $this->setTitle('Group memberships - User');
        $this->template->links = $this->createBackUrl('profile', ['userId' => $this->httpRequest->get('userId')]);
    }

    protected function createComponentGroupMembershipsGrid() {
        $grid = $this->componentFactory->getGridBuilder($this->containerId);

        $qb = $this->groupManager->composeQueryForGroupsWhereUserIsMember($this->httpRequest->get('userId'));

        $grid->createDataSourceFromQueryBuilder($qb, 'groupId');
        $grid->addQueryDependency('userId', $this->httpRequest->get('userId'));

        $grid->addColumnConst('title', 'Title', SystemGroups::class);

        $grid->disablePagination();
        $grid->disableActions();
        $grid->disableRefresh();

        return $grid;
    }

    public function renderChangeProfilePictureForm() {}

    protected function createComponentChangeProfilePictureForm() {
        $form = $this->componentFactory->getFormBuilder();

        $form->setAction($this->createURL('changeProfilePictureFormSubmit'));

        $form->addFileInput('profilePictureFile', 'File:')
            ->setRequired();

        $form->addSubmit('Change');

        return $form;
    }

    public function handleChangeProfilePictureFormSubmit(FormRequest $fr) {
        try {
            // delete old picture
            if($this->getUser()->getProfilePictureFileId() !== null) {
                $file = $this->app->fileStorageManager->getFileById($this->getUser()->getProfilePictureFileId());

                try {
                    $this->app->fileStorageRepository->beginTransaction(__METHOD__);

                    $this->app->fileStorageManager->deleteFile($this->getUser()->getProfilePictureFileId());

                    FileManager::deleteFile($file->filepath);

                    $this->app->fileStorageRepository->commit($this->getUserId(), __METHOD__);
                } catch(AException $e) {
                    $this->app->fileStorageRepository->rollback(__METHOD__);

                    $this->logger->error('Could not delete profile picture for user #' . $this->getUserId() . '. File ID: #' . $this->getUser()->getProfilePictureFileId() . '. Reason: ' . $e->getMessage(), __METHOD__);
                }
            }

            // upload new picture
            $fum = new FileUploadManager();

            $fileData = $fum->uploadImage($_FILES['profilePictureFile'], $this->getUserId(), $this->containerId);

            $this->app->fileStorageRepository->beginTransaction(__METHOD__);

            // create a new database entry for the file
            $fileId = $this->app->fileStorageManager->createNewFile(
                $this->getUserId(),
                $fileData[FileUploadManager::FILE_FILENAME],
                $fileData[FileUploadManager::FILE_FILEPATH],
                $fileData[FileUploadManager::FILE_FILESIZE],
                $this->containerId
            );
            
            // update the user with the new profile picture
            $this->app->userManager->updateUser(
                $this->getUserId(),
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

        $this->redirect($this->createURL('profile', ['userId' => $this->getUserId()]));
    }
}

?>