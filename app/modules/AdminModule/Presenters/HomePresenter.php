<?php

namespace App\Modules\AdminModule;

use App\Components\Widgets\AboutContainerWidget\AboutContainerWidget;
use App\Constants\SessionNames;
use App\Core\Datetypes\DateTime;
use App\Core\Http\HttpRequest;
use App\Helpers\ColorHelper;
use App\UI\FormBuilder2\JSON2FB;

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

    public function renderJsontofb() {
        $json = [
            'name' => 'JsonForm',
            'description' => 'Test',
            'action' => $this->createURL('jsontofb'),
            'elements' => [
                [
                    'name' => 'username',
                    'type' => 'text',
                    'label' => 'Username:',
                    'attributes' => [
                        'required' => true
                    ]
                ],
                [
                    'name' => 'password',
                    'type' => 'password',
                    'label' => 'Password:',
                    'attributes' => [
                        'required' => true
                    ]
                ],
                [
                    'name' => 'age',
                    'type' => 'select',
                    'label' => 'Age:',
                    'attributes' => [
                        'required' => true
                    ],
                    'values' => [
                        '1-18',
                        '19-65',
                        '65-100'
                    ]
                ],
                [
                    'name' => 'submit',
                    'type' => 'submit',
                    'text' => 'Submit'
                ]
            ]
        ];

        $form = $this->componentFactory->getFormBuilder();

        $json2Fb = new JSON2FB($form, $json, $this->containerId);
        
        $this->template->json_form = $json2Fb->render();
    }
}

?>