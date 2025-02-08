<?php

namespace App\Modules\AdminModule;

use App\Constants\ContainerEnvironments;
use App\Core\Datetypes\DateTime;
use App\Core\Http\HttpRequest;
use App\Helpers\ColorHelper;

class HomePresenter extends AAdminPresenter {
    public function __construct() {
        parent::__construct('HomePresenter', 'Home');
    }

    public function renderDashboard() {}

    protected function createComponentContainerInfoForm(HttpRequest $request) {
        $containerId = $this->httpSessionGet('container');
        $container = $this->app->containerManager->getContainerById($containerId);

        $groupUsers = $this->app->groupManager->getGroupUsersForGroupTitle($container->title . ' - users');

        $form = $this->componentFactory->getFormBuilder();

        $form->addTextInput('containerId', 'Container ID:')
            ->setDisabled()
            ->setValue($containerId);

        $form->addTextInput('containerTitle', 'Container title:')
            ->setDisabled()
            ->setValue($container->title);

        $form->addNumberInput('containerUserCount', 'Container users:')
            ->setDisabled()
            ->setValue(count($groupUsers));

        if($container->canShowContainerReferent) {
            $user = $this->app->userManager->getUserById($container->userId);

            $form->addTextInput('containerReferent', 'Container referent:')
                ->setDisabled()
                ->setValue($user->getFullname());
        }

        $dateCreated = new DateTime(strtotime($container->dateCreated));

        $form->addDateTimeInput('containerDateCreated', 'Date created:')
            ->setDisabled()
            ->setValue($dateCreated);

        $form->addTextInput('containerEnvironment', 'Container environment:')
            ->setDisabled()
            ->setValue(ContainerEnvironments::toString($container->environment));

        return $form;
    }

    public function renderColorCombo() {
        [$fgColor, $bgColor] = ColorHelper::createColorCombination();
        $this->template->color_combo = '<div style="color: ' . $fgColor . '; background-color: ' . $bgColor . '; width: 1000px; height: 100px; text-align: center; font-size: 20px; border: 1px solid ' . $fgColor . '">Lorem ipsum (FG: ' . $fgColor . ', BG: ' . $bgColor . ')</div>';
    }
}

?>