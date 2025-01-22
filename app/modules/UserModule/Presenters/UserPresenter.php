<?php

namespace App\Modules\UserModule;

use App\Constants\Container\SystemGroups;
use App\Core\Http\HttpRequest;
use App\Exceptions\AException;
use App\Helpers\DateTimeFormatHelper;

class UserPresenter extends AUserPresenter {
    public function __construct() {
        parent::__construct('UserPresenter', 'User');
    }

    public function handleProfile() {
        $userId = $this->httpRequest->query('userId');

        if($userId === null) {
            $this->redirect($this->createURL('profile', ['userId' => $this->getUserId()]));
        }

        try {
            $user = $this->app->userManager->getUserById($userId);
        } catch(AException $e) {
            $this->flashMessage($e->getMessage(), 'error', 10);
            $this->redirect($this->createFullURL('User:Home', 'dashboard'));
        }

        $this->saveToPresenterCache('username', $user->getUsername());

        // USER PROFILE
        $userProfile = '';
        $addInfo = function(string $title, string $data) use (&$userProfile) {
            $userProfile .= '<p><b>' . $title . ':</b> ' . $data . '</p>';
        };

        $addInfo('Full name', $user->getFullname());
        $addInfo('Email', ($user->getEmail() ?? '-'));
        $addInfo('Member since', DateTimeFormatHelper::formatDateToUserFriendly($user->getDateCreated()));
        $addInfo('ID', $user->getId());

        $this->saveToPresenterCache('userProfile', $userProfile);
        // END OF USER PROFILE

        $this->setTitle('User profile');
    }

    public function renderProfile() {
        $this->template->username = $this->loadFromPresenterCache('username');
        $this->template->user_profile = $this->loadFromPresenterCache('userProfile');
    }

    protected function createComponentUserGroupMembershipsGrid(HttpRequest $request) {
        $grid = $this->componentFactory->getGridBuilder($this->containerId);

        $qb = $this->groupManager->composeQueryForGroupsWhereUserIsMember($request->query('userId'));

        $grid->createDataSourceFromQueryBuilder($qb, 'groupId');
        $grid->addQueryDependency('userId', $request->query('userId'));

        $grid->addColumnConst('title', 'Title', SystemGroups::class);

        return $grid;
    }
}

?>