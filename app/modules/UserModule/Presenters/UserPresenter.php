<?php

namespace App\Modules\UserModule;

use App\Constants\Container\SystemGroups;
use App\Core\Http\FormRequest;
use App\Core\Http\HttpRequest;
use App\Exceptions\AException;
use App\Helpers\DateTimeFormatHelper;
use App\UI\LinkBuilder;

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

        $links = [];
        if($this->getUserId() == $userId) {
            // current user

            if(!$this->app->userAbsenceManager->isUserAbsent($this->getUserId())) {
                $links[] = LinkBuilder::createSimpleLink('Set out-of-office', $this->createURL('outOfOfficeForm'), 'link');
            } else {
                $absence = $this->app->userAbsenceManager->getUserCurrentAbsence($this->getUserId());
                $links[] = '<span>You are currently out-of-office until: ' . DateTimeFormatHelper::formatDateToUserFriendly($absence->dateTo, 'd.m.Y') . '. </span>';
                $links[] = LinkBuilder::createSimpleLink('Clear out-of-office', $this->createURL('clearOutOfOffice', ['absenceId' => $absence->absenceId]), 'link');
            }
        }

        $this->saveToPresenterCache('links', implode('&nbsp;&nbsp;', $links));
    }

    public function renderProfile() {
        $this->template->username = $this->loadFromPresenterCache('username');
        $this->template->user_profile = $this->loadFromPresenterCache('userProfile');
        $this->template->links = $this->loadFromPresenterCache('links');
    }

    protected function createComponentUserGroupMembershipsGrid(HttpRequest $request) {
        $grid = $this->componentFactory->getGridBuilder($this->containerId);

        $qb = $this->groupManager->composeQueryForGroupsWhereUserIsMember($request->query('userId'));

        $grid->createDataSourceFromQueryBuilder($qb, 'groupId');
        $grid->addQueryDependency('userId', $request->query('userId'));

        $grid->addColumnConst('title', 'Title', SystemGroups::class);

        return $grid;
    }

    public function handleOutOfOfficeForm(?FormRequest $fr = null) {
        if($fr !== null) {
            $data = $fr->getData();

            try {
                $this->app->userAbsenceRepository->beginTransaction(__METHOD__);

                $this->app->userAbsenceManager->createUserAbsence($this->getUserId(), $data['dateFrom'], $data['dateTo']);

                $this->app->userAbsenceRepository->commit($this->getUserId(), __METHOD__);

                $this->flashMessage('Out-of-office successfully saved.', 'success');
            } catch(AException $e) {
                $this->app->userAbsenceRepository->rollback(__METHOD__);

                $this->flashMessage('Could not save out-of-office. Reason: ' . $e->getMessage(), 'error', 10);
            }

            $this->redirect($this->createURL('profile', ['userId' => $this->getUserId()]));
        }
    }

    public function renderOutOfOfficeForm() {
        $this->template->links = [
            $this->createBackUrl('profile', ['userId' => $this->getUserId()])
        ];
    }

    protected function createComponentOutOfOfficeForm(HttpRequest $request) {
        $form = $this->componentFactory->getFormBuilder();

        $form->setAction($this->createURL('outOfOfficeForm'));

        $form->addDateInput('dateFrom', 'Date from:')
            ->setRequired();

        $form->addDateInput('dateTo', 'Date to:')
            ->setRequired();

        $form->addSubmit();

        return $form;
    }

    public function handleClearOutOfOffice() {
        $absenceId = $this->httpRequest->query('absenceId');

        try {
            $this->app->userAbsenceRepository->beginTransaction(__METHOD__);

            $this->app->userAbsenceManager->updateUserAbsence($absenceId, ['active' => '0']);

            $this->app->userAbsenceRepository->commit($this->getUserId(), __METHOD__);

            $this->flashMessage('Sucessfully cleared out-of-office.', 'success');
        } catch(AException $e) {
            $this->app->userAbsenceRepository->rollback(__METHOD__);

            $this->flashMessage('Could not clear out-of-office. Reason: ' . $e->getMessage(), 'error', 10);
        }

        $this->redirect($this->createURL('profile', ['userId' => $this->getUserId()]));
    }
}

?>