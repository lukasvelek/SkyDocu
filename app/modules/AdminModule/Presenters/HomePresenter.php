<?php

namespace App\Modules\AdminModule;

use App\Components\Widgets\AboutContainerWidget\AboutContainerWidget;
use App\Constants\ContainerEnvironments;
use App\Constants\SessionNames;
use App\Core\Datetypes\DateTime;
use App\Core\Http\HttpRequest;
use App\Helpers\ColorHelper;

class HomePresenter extends AAdminPresenter {
    public function __construct() {
        parent::__construct('HomePresenter', 'Home');
    }

    public function renderDashboard() {}

    protected function createComponentContainerInfoForm(HttpRequest $request) {
        $containerId = $this->httpSessionGet(SessionNames::CONTAINER);
        $container = $this->app->containerManager->getContainerById($containerId);

        $groupUsers = $this->app->groupManager->getGroupUsersForGroupTitle($container->getTitle() . ' - users');
        $groupGeneralUsers = [];
        $groupTechnicalUsers = [];
        foreach($groupUsers as $userId) {
            $user = $this->app->userManager->getUserById($userId);
            if($user->isTechnical()) {
                $groupTechnicalUsers[] = $user;
            } else {
                $groupGeneralUsers[] = $user;
            }
        }

        $form = $this->componentFactory->getFormBuilder();

        $form->addTextInput('containerId', 'Container ID:')
            ->setDisabled()
            ->setValue($containerId);

        $form->addTextInput('containerTitle', 'Container title:')
            ->setDisabled()
            ->setValue($container->getTitle());

        $form->addTextInput('containerUserCount', 'Container users / technical users:')
            ->setDisabled()
            ->setValue(count($groupGeneralUsers) . ' / ' . count($groupTechnicalUsers));

        if($container->canShowContainerReferent()) {
            $user = $this->app->userManager->getUserById($container->getUserId());

            $form->addTextInput('containerReferent', 'Container referent:')
                ->setDisabled()
                ->setValue($user->getFullname());
        }

        $dateCreated = new DateTime(strtotime($container->getDateCreated()));

        $form->addDateTimeInput('containerDateCreated', 'Date created:')
            ->setDisabled()
            ->setValue($dateCreated);

        $form->addTextInput('containerEnvironment', 'Container environment:')
            ->setDisabled()
            ->setValue(ContainerEnvironments::toString($container->getEnvironment()));

        return $form;
    }

    protected function createComponentContainerInfoWidget(HttpRequest $request) {
        $widget = new AboutContainerWidget($request, $this->app->containerManager, $this->app->groupManager, $this->app->userManager, $this->containerId);

        return $widget;
    }

    public function renderColorCombo() {
        [$fgColor, $bgColor] = ColorHelper::createColorCombination();
        $this->template->color_combo = '<div style="color: ' . $fgColor . '; background-color: ' . $bgColor . '; width: 1000px; height: 100px; text-align: center; font-size: 20px; border: 1px solid ' . $fgColor . '">Lorem ipsum (FG: ' . $fgColor . ', BG: ' . $bgColor . ')</div>';
    }
}

?>