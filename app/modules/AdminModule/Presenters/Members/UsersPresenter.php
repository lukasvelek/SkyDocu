<?php

namespace App\Modules\AdminModule;

use App\Constants\Container\SystemGroups;
use App\Core\Caching\CacheNames;
use App\Core\Http\FormRequest;
use App\Core\Http\HttpRequest;
use App\Exceptions\AException;
use App\UI\LinkBuilder;

class UsersPresenter extends AAdminPresenter {
    public function __construct() {
        parent::__construct('UsersPresenter', 'Users');

        $this->setMembers();
    }

    public function renderList() {
        $this->template->links = [
            LinkBuilder::createSimpleLink('New user', $this->createURL('newUserForm'), 'link')
        ];
    }

    protected function createComponentUsersGrid(HttpRequest $request) {
        $grid = $this->componentFactory->getGridBuilder($this->containerId);

        $userIds = $this->groupRepository->getMembersForGroup($this->groupRepository->getGroupByTitle(SystemGroups::ALL_USERS)['groupId']);

        $qb = $this->app->userRepository->composeQueryForUsers();
        $qb->andWhere($qb->getColumnInValues('userId', $userIds));

        $grid->createDataSourceFromQueryBuilder($qb, 'userId');

        $grid->addColumnText('fullname', 'Fullname');
        $grid->addColumnText('username', 'Username');
        $grid->addColumnText('email', 'Email');
        $grid->addColumnBoolean('isTechnical', 'Technical user');

        $grid->addQuickSearch('fullname', 'Fullname');
        $grid->addQuickSearch('username', 'Username');

        $grid->addFilter('isTechnical', 0, ['0' => 'False', '1' => 'True']);

        return $grid;
    }

    public function handleNewUserForm(?FormRequest $fr = null) {
        if($fr !== null) {
            // Add user to master users
            // Add user to container All users group

            try {
                $this->app->userRepository->beginTransaction(__METHOD__);

                $email = $fr->email;
                if($email == '') {
                    $email = null;
                }

                $userId = $this->app->userManager->createNewUser($fr->username, $fr->fullname, password_hash($fr->password, PASSWORD_BCRYPT), $email);

                $containerId = $this->httpSessionGet('container');
                $container = $this->app->containerManager->getContainerById($containerId);

                $masterTableName = $container->getTitle() . ' - users';
                $group = $this->app->groupManager->getGroupByTitle($masterTableName);

                $this->app->groupManager->addUserToGroup($userId, $group->groupId);

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
        $this->template->links = [
            LinkBuilder::createSimpleLink('&larr; Back', $this->createURL('list'), 'link')
        ];
    }

    protected function createComponentNewUserForm(HttpRequest $request) {
        $form = $this->componentFactory->getFormBuilder();

        $form->setAction($this->createURL('newUserForm'));

        $form->addTextInput('username', 'Username:')
            ->setRequired();

        $form->addTextInput('fullname', 'Fullname:')
            ->setRequired();

        $form->addPasswordInput('password', 'Password:')
            ->setRequired();

        $form->addEmailInput('emai', 'Email:');

        $form->addSubmit('Create');

        return $form;
    }
}

?>