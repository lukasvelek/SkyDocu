<?php

namespace App\Modules\UserModule;

use App\Components\Static\UserProfileStatic\UserProfileStatic;
use App\Components\UserSubstituteForm\UserSubstituteForm;
use App\Constants\AppDesignThemes;
use App\Constants\DateFormats;
use App\Constants\TimeFormats;
use App\Core\Http\FormRequest;
use App\Exceptions\AException;
use App\Helpers\LinkHelper;
use App\Lib\Forms\Reducers\UserOutOfOfficeFormReducer;
use App\UI\LinkBuilder;

class UserConfigurationPresenter extends AUserPresenter {
    public function __construct() {
        parent::__construct('UserConfigurationPresenter', 'Configuration');
    }
    
    protected function createComponentSidebar() {
        $userId = $this->httpRequest->get('userId');
        $action = $this->httpRequest->get('action');

        $sidebar = $this->componentFactory->getSidebar();

        $params = [
            'userId' => $userId
        ];

        $links = [
            [
                'title' => '&larr; Back',
                'url' => $this->createFullURL('User:User', 'profile', $params),
                'name' => 'back'
            ],
            [
                'title' => 'Home',
                'url' => $this->createURL('home', $params),
                'name' => 'home'
            ],
            [
                'title' => 'Absence',
                'url' => $this->createURL('outOfOfficeForm', $params),
                'name' => 'outOfOfficeForm'
            ],
            [
                'title' => 'Substitute',
                'url' => $this->createURL('substituteForm', $params),
                'name' => 'substituteForm'
            ],
            [
                'title' => 'Appearance',
                'url' => $this->createURL('appearanceForm', $params),
                'name' => 'appearanceForm'
            ]
        ];

        foreach($links as $link) {
            $sidebar->addLink($link['title'], $link['url'], ($link['name'] == $action));
        }

        return $sidebar;
    }

    // ################
    // #     HOME     #
    // ################

    public function renderHome() {
        $this->setTitle('Home - Configuration');
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

    // #######################
    // #     END OF HOME     #
    // #######################

    // #########################
    // #     OUT OF OFFICE     #
    // #########################

    public function renderOutOfOfficeForm() {
        $this->setTitle('Absence - Configuration');

        $userId = $this->httpRequest->get('userId');

        $links = [];
        if($this->app->userAbsenceManager->isUserAbsent($userId)) {
            $links[] = LinkBuilder::createSimpleLink('Clear absence', $this->createURL('clearOutOfOffice', ['userId' => $userId]), 'link');
        }

        $this->template->links = LinkHelper::createLinksFromArray($links);
    }

    protected function createComponentOutOfOfficeForm() {
        $form = $this->componentFactory->getFormBuilder();

        $form->setAction($this->createURL('outOfOfficeFormSubmit'));

        $form->addDateInput('dateFrom', 'Date from:')
            ->setMinimum(date('Y-m-d'))
            ->setRequired();

        $form->addDateInput('dateTo', 'Date to:')
            ->setMinimum(date('Y-m-d'))
            ->setRequired();

        $form->addSubmit();

        $form->setCallReducerOnChange();
        $form->reducer = new UserOutOfOfficeFormReducer($this->app, $this->httpRequest);

        return $form;
    }

    public function handleOutOfOfficeFormSubmit(FormRequest $fr) {
        $data = $fr->getData();

        try {
            $this->app->userAbsenceRepository->beginTransaction(__METHOD__);

            $this->app->userAbsenceManager->createUserAbsence($this->getUserId(), $data['dateFrom'], $data['dateTo']);

            $this->app->userAbsenceRepository->commit($this->getUserId(), __METHOD__);

            $this->flashMessage('Absence successfully saved.', 'success');
        } catch(AException $e) {
            $this->app->userAbsenceRepository->rollback(__METHOD__);

            $this->flashMessage('Could not save absence. Reason: ' . $e->getMessage(), 'error', 10);
        }

        $this->redirect($this->createURL('outOfOfficeForm', ['userId' => $this->getUserId()]));
    }

    public function handleClearOutOfOffice() {
        $userId = $this->httpRequest->get('userId');

        try {
            $absence = $this->app->userAbsenceManager->getUserCurrentAbsence($userId);

            $this->app->userAbsenceRepository->beginTransaction(__METHOD__);

            $this->app->userAbsenceManager->updateUserAbsence($absence->absenceId, [
                'active' => 0
            ]);

            $this->app->userAbsenceRepository->commit($this->getUserId(), __METHOD__);

            $this->flashMessage('Successfully cleared absence.', 'success');
        } catch(AException $e) {
            $this->app->userAbsenceRepository->rollback(__METHOD__);

            $this->flashMessage('Could not clear absence. Reason: ' . $e->getMessage(), 'error', 10);
        }

        $this->redirect($this->createURL('outOfOfficeForm', ['userId' => $userId]));
    }

    // ################################
    // #     END OF OUT OF OFFICE     #
    // ################################

    // #####################
    // #     SUBSTIUTE     #
    // #####################

    public function renderSubstituteForm() {
        $this->setTitle('Substitute - Configuration');

        $userId = $this->httpRequest->get('userId');

        $links = [];
        if($this->app->userSubstituteManager->getUserSubstitute($userId) !== null) {
            $links[] = LinkBuilder::createSimpleLink('Clear substitute', $this->createURL('clearSubstitute', ['userId' => $userId]), 'link');
        }

        $this->template->links = LinkHelper::createLinksFromArray($links);
    }

    protected function createComponentSubstituteForm() {
        $form = new UserSubstituteForm($this->httpRequest, $this->app->userRepository);

        $form->setCurrentUserId($this->getUserId());
        $form->setAction($this->createURL('substituteFormSubmit', ['userId' => $this->httpRequest->get('userId')]));

        return $form;
    }

    public function handleSubstituteFormSubmit(FormRequest $fr) {
        $data = $fr->getData();

        try {
            $this->app->userSubstituteRepository->beginTransaction(__METHOD__);

            $this->app->userSubstituteManager->setUserAbstitute($this->getUserId(), $data['user']);

            $this->app->userSubstituteRepository->commit($this->getUserId(), __METHOD__);

            $this->flashMessage('Successfully changed substitute.', 'success');
        } catch(AException $e) {
            $this->app->userSubstituteRepository->rollback(__METHOD__);

            $this->flashMessage('Could not change substitute. Reason: ' . $e->getMessage(), 'error', 10);
        }

        $this->redirect($this->createURL('profile', ['userId' => $this->getUserId()]));
    }

    public function handleClearSubstitute() {
        $userId = $this->httpRequest->get('userId');

        try {
            $this->app->userSubstituteRepository->beginTransaction(__METHOD__);

            $this->app->userSubstituteManager->removeUserSubstitute($userId);

            $this->app->userSubstituteRepository->commit($this->getUserId(), __METHOD__);

            $this->flashMessage('Successfully cleared substitute.', 'success');
        } catch(AException $e) {
            $this->app->userSubstituteRepository->rollback(__METHOD__);

            $this->flashMessage('Could not clear substitute. Reason: ' . $e->getMessage(), 'error', 10);
        }

        $this->redirect($this->createURL('substituteForm', ['userId' => $userId]));
    }

    // ############################
    // #     END OF SUBSTIUTE     #
    // ############################

    // ######################
    // #     APPEARANCE     #
    // ######################

    public function renderAppearanceForm() {
        $this->setTitle('Appearance - Configuration');
    }

    protected function createComponentAppearanceForm() {
        $userId = $this->httpRequest->get('userId');

        try {
            $user = $this->app->userManager->getUserById($userId);
        } catch(AException $e) {
            $this->flashMessage($e->getMessage(), 'error', 10);
            $this->redirect($this->createFullURL('User:Home', 'dashboard'));
        }

        $form = $this->componentFactory->getFormBuilder();

        $form->setAction($this->createURL('appearanceFormSubmit', ['userId' => $this->getUserId()]));

        // App Themes
        $themes = [];
        foreach(AppDesignThemes::getAll() as $key => $value) {
            $theme = [
                'value' => $key,
                'text' => $value
            ];

            if($user->getAppDesignTheme() == $key) {
                $theme['selected'] = 'selected';
            }

            $themes[] = $theme;
        }

        // Date formats
        $dateFormats = [];
        foreach(DateFormats::FORMATS as $date) {
            $format = [
                'value' => $date,
                'text' => $date
            ];

            if($user->getDateFormat() == $date) {
                $format['selected'] = 'selected';
            }

            $dateFormats[] = $format;
        }

        // Time formats
        $timeFormats = [];
        foreach(TimeFormats::FORMATS as $time) {
            $format = [
                'value' => $time,
                'text' => $time
            ];

            if($user->getTimeFormat() == $time) {
                $format['selected'] = 'selected';
            }

            $timeFormats[] = $format;
        }

        $form->addSelect('theme', 'Theme:')
            ->addRawOptions($themes);

        $form->addSelect('dateFormat', 'Date format:')
            ->addRawOptions($dateFormats);

        $form->addSelect('timeFormat', 'Time format:')
            ->addRawOptions($timeFormats);

        $form->addSubmit('Save');

        return $form;
    }

    public function handleAppearanceFormSubmit(FormRequest $fr) {
        $userId = $this->httpRequest->get('userId');

        $data = $fr->getData();

        try {
            $this->app->userRepository->beginTransaction(__METHOD__);

            $this->app->userManager->updateUser($userId, [
                'appDesignTheme' => $data['theme'],
                'dateFormat' => $data['dateFormat'],
                'timeFormat' => $data['timeFormat']
            ]);

            $this->app->userRepository->commit($this->getUserId(), __METHOD__);

            $this->flashMessage('Successfully saved appearance changes. Changes might take a few seconds before becoming visible.', 'success');
        } catch(AException $e) {
            $this->app->userRepository->rollback(__METHOD__);

            $this->flashMessage('Could not save appearance changes. Reason: ' . $e->getMessage(), 'error', 10);
        }

        $this->redirect($this->createURL('appearanceForm', ['userId' => $userId]));
    }

    // #############################
    // #     END OF APPEARANCE     #
    // #############################
}