<?php

namespace App\Modules\AdminModule;

use App\Constants\Container\SystemGroups;
use App\Core\AjaxRequestBuilder;
use App\Core\Caching\CacheNames;
use App\Core\Datetypes\DateTime;
use App\Core\DB\DatabaseRow;
use App\Core\HashManager;
use App\Core\Http\Ajax\Operations\CustomOperation;
use App\Core\Http\Ajax\Requests\PostAjaxRequest;
use App\Core\Http\FormRequest;
use App\Core\Http\HttpRequest;
use App\Core\Http\JsonResponse;
use App\Exceptions\AException;
use App\Helpers\LinkHelper;
use App\UI\FormBuilder2\PasswordInput;
use App\UI\GridBuilder2\Action;
use App\UI\GridBuilder2\Row;
use App\UI\HTML\HTML;
use App\UI\LinkBuilder;

class UsersPresenter extends AAdminPresenter {
    public function __construct() {
        parent::__construct('UsersPresenter', 'Users');

        $this->setMembers();
    }

    public function renderList() {
        $this->template->links = LinkBuilder::createSimpleLink('New user', $this->createURL('newUserForm'), 'link');
    }

    protected function createComponentUsersGrid(HttpRequest $request) {
        $grid = $this->componentFactory->getGridBuilder($this->containerId);

        $userIds = $this->groupRepository->getMembersForGroup($this->groupRepository->getGroupByTitle(SystemGroups::ALL_USERS)['groupId']);

        $qb = $this->app->userRepository->composeQueryForUsers();
        $qb->andWhere($qb->getColumnInValues('userId', $userIds));

        $grid->createDataSourceFromQueryBuilder($qb, 'userId');

        $grid->addColumnText('fullname', 'Fullname');
        $grid->addColumnText('email', 'Email');
        $grid->addColumnText('userId', 'ID');
        $grid->addColumnBoolean('isTechnical', 'Technical user');
        $grid->addColumnBoolean('isDeleted', 'Is deleted');

        $grid->addQuickSearch('fullname', 'Fullname');
        $grid->addQuickSearch('email', 'Email');

        $grid->addFilter('isTechnical', 0, ['0' => 'False', '1' => 'True']);
        $grid->addFilter('isDeleted', 0, ['0' => 'False', '1' => 'True']);

        $edit = $grid->addAction('edit');
        $edit->setTitle('Edit');
        $edit->onCanRender[] = function(DatabaseRow $row, Row $_row, Action &$action) {
            return !(bool)$row->isDeleted;
        };
        $edit->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
            $el = HTML::el('a');
            $el->class('grid-link')
                ->text('Edit')
                ->href($this->createURLString('editUserForm', ['userId' => $primaryKey]));

            return $el;
        };

        $delete = $grid->addAction('delete');
        $delete->setTitle('Delete');
        $delete->onCanRender[] = function(DatabaseRow $row, Row $_row, Action &$action) use ($userIds) {
            if($row->isDeleted == true) {
                return false;
            }

            // if there is at least one more user
            if(count($userIds) == 1) {
                return false;
            }

            return true;
        };
        $delete->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
            $el = HTML::el('a');
            $el->class('grid-link')
                ->text('Delete')
                ->href($this->createURLString('deleteUser', ['userId' => $primaryKey]));

            return $el;
        };

        $restore = $grid->addAction('restore');
        $restore->setTitle('Restore');
        $restore->onCanRender[] = function(DatabaseRow $row, Row $_row, Action &$action) {
            return (bool)$row->isDeleted;
        };
        $restore->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
            $el = HTML::el('a');
            $el->class('grid-link')
                ->text('Restore')
                ->href($this->createURLString('restoreUser', ['userId' => $primaryKey]));

            return $el;
        };

        return $grid;
    }

    public function handleNewUserForm(?FormRequest $fr = null) {
        if($fr !== null) {
            // Add user to master users
            // Add user to container All users group

            try {
                // USER CREATION
                $this->app->userRepository->beginTransaction(__METHOD__);

                $userId = $this->app->userManager->createNewUser($fr->email, $fr->fullname, HashManager::hashPassword($fr->password));

                $this->app->userRepository->commit($this->getUserId(), __METHOD__);

                // USER UPDATE
                $this->app->userRepository->beginTransaction(__METHOD__);

                $updateData = [];

                if($fr->superiorUser != 'null') {
                    $updateData['superiorUserid'] = $fr->superiorUser;
                }
                if($fr->orgPosition !== null) {
                    $updateData['orgPosition'] = $fr->orgPosition;
                }
                if($fr->orgDepartment !== null) {
                    $updateData['orgDepartment'] = $fr->orgDepartment;
                }
                if($fr->orgSection !== null) {
                    $updateData['orgSection'] = $fr->orgSection;
                }
                if($fr->personalNumber !== null) {
                    $updateData['personalNumber'] = $fr->personalNumber;
                }

                if(!empty($updateData)) {
                    $this->app->userManager->updateUser($userId, $updateData);
                }

                // ADD USER TO CONTAINER GROUP
                $containerId = $this->httpSessionGet('container');
                $container = $this->app->containerManager->getContainerById($containerId);

                $masterTableName = $container->getTitle() . ' - users';
                $group = $this->app->groupManager->getGroupByTitle($masterTableName);

                $this->app->groupManager->addUserToGroup($userId, $group->groupId);

                // ADD USER TO ALL USERS IN CONTAINER
                $this->groupRepository->beginTransaction(__METHOD__);
                
                $this->groupManager->addUserToGroupTitle(SystemGroups::ALL_USERS, $userId);

                $this->cacheFactory->invalidateCacheByNamespace(CacheNames::GROUP_MEMBERSHIPS);
                $this->cacheFactory->invalidateCacheByNamespace(CacheNames::USER_GROUP_MEMBERSHIPS);

                $this->groupRepository->commit($this->getUserId(), __METHOD__);

                $this->app->userRepository->commit($this->getUserId(), __METHOD__);

                $this->flashMessage('User created successfully.', 'success');
            } catch(AException $e) {
                $this->groupRepository->rollback(__METHOD__);
                $this->app->userRepository->rollback(__METHOD__);
                
                $this->flashMessage('Could not create user. Reason: ' . $e->getMessage(), 'error', 10);
            }

            $this->redirect($this->createURL('list'));
        }
    }

    public function renderNewUserForm() {
        $this->template->links = LinkBuilder::createSimpleLink('&larr; Back', $this->createURL('list'), 'link');
    }

    protected function createComponentNewUserForm(HttpRequest $request) {
        $form = $this->componentFactory->getFormBuilder();

        $form->setAction($this->createURL('newUserForm'));

        $form->addEmailInput('email', 'Email:')
            ->setRequired();

        $form->addTextInput('fullname', 'Fullname:')
            ->setRequired();

        $form->addPasswordInput('password', 'Password:')
            ->setRequired()
            ->setPasswordComplexityRequirements(6, 32, PasswordInput::COMPLEXITY_TEXT_NUMBERS_SPECIAL_CHARS);

        $form->addHorizontalLine();

        $form->addTextInput('userSearchQuery', 'Search user by fullname:');
        $form->addButton('Search')
            ->setOnClick('_searchUsers()');

        $form->addSelect('superiorUser', 'Superior user:')
            ->addRawOption('null', 'Not selected', true);

        $form->addHorizontalLine();

        $form->addTextInput('orgPosition', 'Position:');
        $form->addTextInput('orgSection', 'Section:');
        $form->addTextInput('orgDepartment', 'Department:');
        $form->addTextInput('personalNumber', 'Personal number:');

        $form->addSubmit('Create');

        $arb = new AjaxRequestBuilder();

        $arb->setMethod('POST')
            ->setAction($this, 'searchUsers')
            ->setHeader([
                'query' => '_query'
            ])
            ->updateHTMLElement('superiorUser', 'users')
            ->setFunctionName('searchUsers')
            ->setFunctionArguments(['_query']);

        $form->addScript($arb);

        $form->addScript('
            async function _searchUsers() {
                const _query = $("#userSearchQuery").val();

                if(_query.length == 0) {
                    return;
                }

                await searchUsers(_query);
            }
        ');

        $par = new PostAjaxRequest($this->httpRequest);
        $par->setUrl($this->createURL('checkEmailForNewUserForm'))
            ->addArgument('_query')
            ->setData(['query' => '_query']);

        $operation = new CustomOperation();

        $operation->addCode('
            if(obj.result == 0) {
                alert("This email is taken.");
                $("#formSubmit").attr("disabled", true);
                $("#email").css("border", "1px solid red");
            } else {
                $("#formSubmit").removeAttr("disabled");
                $("#email").css("border", "1px solid black");
            }
        ');

        $par->addOnFinishOperation($operation);

        $form->addScript($par);

        $form->addScript('
            async function checkEmailExists() {
                const query = $("#email").val();

                await ' . $par->getFunctionName() . '(query);
            }

            $("form").on("focusout", "#email", async function() {
                await checkEmailExists();
            });
        ');

        return $form;
    }

    public function actionCheckEmailForNewUserForm() {
        $query = $this->httpRequest->get('query');

        $users = $this->app->userRepository->searchUsers($query, ['email']);

        return new JsonResponse([
            'result' => empty($users) ? 1 : 0
        ]);
    }

    public function renderEditUserForm() {
        $links = [
            $this->createBackUrl('list')
        ];

        $this->template->links = LinkHelper::createLinksFromArray($links);
    }

    protected function createComponentEditUserForm() {
        $userId = $this->httpRequest->get('userId');

        try {
            $user = $this->app->userManager->getUserById($userId);
        } catch(AException $e) {
            $this->flashMessage(sprintf('User \'%s\' does not exist.'), 'error');
            $this->redirect($this->createURL('list'));
        }

        $form = $this->componentFactory->getFormBuilder();

        $form->setAction($this->createURL('editUserFormSubmit', ['userId' => $userId]));

        $form->addTextInput('fullname', 'Fullname:')
            ->setRequired()
            ->setValue($user->getFullname());

        $form->addHorizontalLine();

        $form->addTextInput('userSearchQuery', 'Search user by fullname:');
        $form->addButton('Search')
            ->setOnClick('_searchUsers()');

        $select = $form->addSelect('superiorUser', 'Superior user:')
            ->addRawOption('null', 'Not selected', true);

        if($user->getSuperiorUserId() !== null) {
            try {
                $superiorUser = $this->app->userManager->getUserById($user->getSuperiorUserId());

                $select->addRawOption($superiorUser->getId(), $superiorUser->getFullname(), true);
            } catch(AException $e) {}
        }

        $form->addHorizontalLine();

        $form->addTextInput('orgPosition', 'Position:')
            ->setValue($user->getOrgPosition());
        $form->addTextInput('orgSection', 'Section:')
            ->setValue($user->getOrgSection());
        $form->addTextInput('orgDepartment', 'Department:')
            ->setValue($user->getOrgDepartment());
        $form->addTextInput('personalNumber', 'Personal number:')
            ->setValue($user->getPersonalNumber());

        $form->addSubmit('Create');

        $arb = new AjaxRequestBuilder();

        $arb->setMethod('POST')
            ->setAction($this, 'searchUsers')
            ->setHeader([
                'query' => '_query'
            ])
            ->updateHTMLElement('superiorUser', 'users')
            ->setFunctionName('searchUsers')
            ->setFunctionArguments(['_query']);

        $form->addScript($arb);

        $form->addScript('
            async function _searchUsers() {
                const _query = $("#userSearchQuery").val();

                if(_query.length == 0) {
                    return;
                }

                await searchUsers(_query);
            }
        ');

        $form->addSubmit('Save');

        return $form;
    }

    public function handleEditUserFormSubmit(FormRequest $fr) {
        try {
            $userId = $this->httpRequest->get('userId');

            $this->app->userRepository->beginTransaction(__METHOD__);

            $this->app->userManager->updateUser($userId, [
                'fullname' => $fr->fullname,
                'superiorUserId' => $fr->superiorUser,
                'orgPosition' => $fr->orgPosition,
                'orgSection' => $fr->orgSection,
                'orgDepartment' => $fr->orgDepartment,
                'personalNumber' => $fr->personalNumber
            ]);

            $this->app->userRepository->commit($this->getUserId(), __METHOD__);

            $this->flashMessage('User has been updated.', 'success');
        } catch(AException $e) {
            $this->app->userRepository->rollback(__METHOD__);

            $this->flashMessage('Could not update user. Reason: ' . $e->getMessage(), 'error', 10);
        }

        $this->redirect($this->createURL('list'));
    }

    public function handleDeleteUser() {
        $userId = $this->httpRequest->get('userId');

        try {
            $this->app->userRepository->beginTransaction(__METHOD__);

            $this->app->userManager->updateUser($userId, [
                'isDeleted' => 1,
                'dateDeleted' => DateTime::now()
            ]);

            $this->app->userRepository->commit($this->getUserId(), __METHOD__);

            $this->flashMessage('Successfully deleted user.', 'success');
        } catch(AException $e) {
            $this->app->userRepository->rollback(__METHOD__);

            $this->flashMessage('Could not delete user. Reason: ' . $e->getMessage(), 'error', 10);
        }

        $this->redirect($this->createURL('list'));
    }

    public function handleRestoreUser() {
        $userId = $this->httpRequest->get('userId');

        try {
            $this->app->userRepository->beginTransaction(__METHOD__);

            $this->app->userManager->updateUser($userId, ['isDeleted' => 0]);

            $this->app->userRepository->commit($this->getUserId(), __METHOD__);

            $this->flashMessage('Successfully restored user.', 'success');
        } catch(AException $e) {
            $this->app->userRepository->rollback(__METHOD__);

            $this->flashMessage('Could not restore user. Reason: ' . $e->getMessage(), 'error', 10);
        }

        $this->redirect($this->createURL('list'));
    }

    public function actionSearchUsers() {
        $query = $this->httpRequest->get('query');

        $users = [];

        if(!$this->isSuperiorUserMandatory()) {
            $users[] = '<option value="null">Not selected</option>';
        }

        $container = $this->app->containerManager->getContainerById($this->containerId);

        $userIds = $this->app->groupManager->getGroupUsersForGroupTitle($container->getTitle() . ' - users');

        $qb = $this->app->userRepository->composeQueryForUsers();

        $qb->andWhere($qb->getColumnInValues('userId', $userIds))
            ->andWhere('(fullname LIKE ? OR email LIKE ?)', ['%' . $query . '%', '%' . $query . '%'])
            ->execute();

        while($row = $qb->fetchAssoc()) {
            $users[] = '<option value="' . $row['userId'] . '">' . $row['fullname'] . '</option>';
        }

        return new JsonResponse(['users' => $users]);
    }

    /**
     * Returns true if superior user must be selected during new user creation
     */
    private function isSuperiorUserMandatory(): bool {
        $container = $this->app->containerManager->getContainerById($this->containerId);

        $userIds = $this->app->groupManager->getGroupUsersForGroupTitle($container->getTitle() . ' - users');

        return count($userIds) > 1;
    }
}

?>