<?php

namespace App\Modules\UserModule;

use App\Components\Static\UserProfileStatic\UserProfileStatic;
use App\Constants\Container\SystemGroups;
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
}

?>