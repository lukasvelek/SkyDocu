<?php

namespace App\Modules\SuperAdminModule;

use App\Components\ContainersGrid\ContainersGrid;
use App\Constants\ContainerStatus;
use App\Core\DB\DatabaseRow;
use App\Core\Http\FormRequest;
use App\Core\Http\HttpRequest;
use App\Exceptions\AException;
use App\Exceptions\GeneralException;
use App\Helpers\GridHelper;
use App\Helpers\LinkHelper;
use App\UI\GridBuilder2\Action;
use App\UI\GridBuilder2\CheckboxLink;
use App\UI\GridBuilder2\Row;
use App\UI\HTML\HTML;
use App\UI\LinkBuilder;

class ContainersPresenter extends ASuperAdminPresenter {
    public function __construct() {
        parent::__construct('ContainersPresenter', 'Containers');
    }

    public function renderList() {
        $links = [];

        if($this->app->groupManager->isUserMemberOfContainerManagers($this->getUserId())) {
            $links[] = LinkBuilder::createSimpleLink('New container', $this->createURL('newContainerForm'), 'link');
        } else {
            $links[] = LinkBuilder::createSimpleLink('New container request', $this->createURL('newContainerRequestForm'), 'link');
        }

        $this->template->links = LinkHelper::createLinksFromArray($links);

        $this->addScript('
            function processBulkAction(data) {
                post(data.url, {"ids": data.ids});
            }
        ');
    }

    protected function createComponentContainersGrid(HttpRequest $request) {
        $grid = $this->componentFactory->getGridBuilderExtendingClassInstance(ContainersGrid::class);

        $grid->addCheckboxes2($this, 'bulkAction');

        $grid->addCheckboxLinkCallback(
            (new CheckboxLink('approveRequest'))
                ->setCheckCallback(function(string $primaryKey) {
                    try {
                        $container = $this->app->containerManager->getContainerById($primaryKey);

                        if($container->getStatus() != ContainerStatus::REQUESTED) {
                            return false;
                        }

                        if(!$this->app->groupManager->isUserMemberOfContainerManagers($this->getUserId())) {
                            return false;
                        }

                        return true;
                    } catch(AException $e) {
                        return false;
                    }
                })
                ->setLinkCallback(function(array $primaryKeys) {
                    $data = [
                        'ids' => $primaryKeys,
                        'url' => $this->createURLString('approveRequest')
                    ];

                    return LinkBuilder::createJSOnclickLink(
                        'Approve request',
                        'processBulkAction(' . htmlspecialchars(json_encode($data)) . ')',
                        'link'
                    );
                })
        );

        $grid->addCheckboxLinkCallback(
            (new CheckboxLink('declineRequest'))
                ->setCheckCallback(function(string $primaryKey) {
                    try {
                        $container = $this->app->containerManager->getContainerById($primaryKey);

                        if($container->getStatus() != ContainerStatus::REQUESTED) {
                            return false;
                        }

                        if(!$this->app->groupManager->isUserMemberOfContainerManagers($this->getUserId())) {
                            return false;
                        }

                        return true;
                    } catch(AException $e) {
                        return false;
                    }
                })
                ->setLinkCallback(function(array $primaryKeys) {
                    $data = [
                        'ids' => $primaryKeys,
                        'url' => $this->createURLString('declineRequest')
                    ];

                    return LinkBuilder::createJSOnclickLink(
                        'Decline request',
                        'processBulkAction(' . htmlspecialchars(json_encode($data)) . ')',
                        'link'
                    );
                })
        );

        $grid->addCheckboxLinkCallback(
            (new CheckboxLink('changeStatus'))
                ->setCheckCallback(function(string $primaryKey) {
                    try {
                        $container = $this->app->containerManager->getContainerById($primaryKey);

                        if(!in_array($container->getStatus(), [ContainerStatus::NOT_RUNNING, ContainerStatus::RUNNING])) {
                            return false;
                        }

                        if(!$this->app->groupManager->isUserMemberOfContainerManagers($this->getUserId())) {
                            return false;
                        }

                        return true;
                    } catch(AException $e) {
                        return false;
                    }
                })
                ->setLinkCallback(function(array $primaryKeys) {
                    $data = [
                        'ids' => $primaryKeys,
                        'url' => $this->createURLString('changeStatusForm')
                    ];

                    return LinkBuilder::createJSOnclickLink(
                        'Change status',
                        'processBulkAction(' . htmlspecialchars(json_encode($data)) . ')',
                        'link'
                    );
                })
        );

        return $grid;
    }

    public function handleNewContainerForm(?FormRequest $fr = null) {
        if($fr !== null) {
            try {
                if($this->app->containerManager->checkContainerTitleExists($fr->title)) {
                    throw new GeneralException('Container with this name already exists.');
                }

                $canShowReferent = false;
                if($fr->isset('canShowReferent') && $fr->canShowReferent == 'on') {
                    $canShowReferent = true;
                }
    
                $containerId = $this->app->containerManager->createNewContainer($fr->title, $fr->description, $this->getUserId(), $canShowReferent);
    
                $this->flashMessage('New container created. Container interface will be generated by background service.', 'success');
            } catch(AException $e) {
                $this->flashMessage('Could not create new container. Reason: ' . $e->getMessage(), 'error', 10);
            }
    
            $this->redirect($this->createURL('list'));
        }
    }

    public function renderNewContainerForm() {
        $this->template->links = LinkBuilder::createSimpleLink('&larr; Back', $this->createURL('list'), 'link');
    }

    protected function createComponentForm(HttpRequest $request) {
        $form = $this->componentFactory->getFormBuilder();

        $form->setAction($this->createURL('newContainerForm'));

        $form->addLabel('basicInformation', 'Basic information')
            ->setTitle();

        $form->addTextInput('title', 'Title:')
            ->setRequired()
            ->setPlaceholder('Container title');

        $form->addTextArea('description', 'Description:')
            ->setPlaceHolder('Description')
            ->setRequired();

        $form->addCheckboxInput('canShowReferent', 'Is referent visible?')
            ->setChecked();

        $form->addSubmit('Submit');

        return $form;
    }

    public function handleNewContainerRequestForm(?FormRequest $fr = null) {
        if($fr !== null) {
            try {
                $this->app->containerRepository->beginTransaction(__METHOD__);

                $canShowReferent = false;
                if($fr->isset('canShowReferent') && $fr->canShowReferent == 'on') {
                    $canShowReferent = true;
                }

                $this->app->containerManager->createNewContainer($fr->title, $fr->description, $this->getUserId(), $canShowReferent, ContainerStatus::REQUESTED);

                $this->app->containerRepository->commit($this->getUserId(), __METHOD__);

                $this->flashMessage('Your container request has been saved. Please wait until it is approved and created.', 'success');
            } catch(AException $e) {
                $this->app->containerRepository->rollback(__METHOD__);

                $this->flashMessage('Could not create container request. Reason: ' . $e->getMessage(), 'error');
            }

            $this->redirect($this->createURL('list'));
        }
    }

    public function renderNewContainerRequestForm() {
        $this->template->links = $this->createBackUrl('list');
    }

    protected function createComponentNewContainerRequestForm(HttpRequest $request) {
        $form = $this->componentFactory->getFormBuilder();

        $form->setAction($this->createURL('newContainerRequestForm'));

        $form->addLabel('basicInformation', 'Basic information')
            ->setTitle();

        $form->addTextInput('title', 'Title:')
            ->setRequired()
            ->setPlaceholder('Container title');

        $form->addTextArea('description', 'Description:')
            ->setPlaceHolder('Description')
            ->setRequired();

        $form->addCheckboxInput('canShowReferent', 'Is referent visible?')
            ->setChecked();

        $form->addSubmit('Submit');

        return $form;
    }

    public function handleApproveRequest() {
        $containerIds = $this->httpRequest->get('ids');

        try {
            $this->app->containerRepository->beginTransaction(__METHOD__);

            foreach($containerIds as $containerId) {
                $this->app->containerManager->approveContainerRequest($containerId, $this->getUserId());
            }

            $this->app->containerRepository->commit($this->getUserId(), __METHOD__);

            $this->flashMessage('Container requests approved. Containers will be created asynchronously.', 'success');
        } catch(AException $e) {
            $this->app->containerRepository->rollback(__METHOD__);

            $this->flashMessage('Could not approve container requests. Reason: ' . $e->getMessage(), 'error');
        }

        $this->redirect($this->createURL('list'));
    }

    public function handleDeclineRequest() {
        $containerIds = $this->httpRequest->get('ids');

        try {
            $this->app->containerRepository->beginTransaction(__METHOD__);

            foreach($containerIds as $containerId) {
                $this->app->containerManager->declineContainerRequest($containerId);
            }

            $this->app->containerRepository->commit($this->getUserId(), __METHOD__);

            $this->flashMessage('Container requests approved. Containers will be created asynchronously.', 'success');
        } catch(AException $e) {
            $this->app->containerRepository->rollback(__METHOD__);

            $this->flashMessage('Could not approve container requests. Reason: ' . $e->getMessage(), 'error');
        }

        $this->redirect($this->createURL('list'));
    }

    public function renderChangeStatusForm() {
        $links = [
            $this->createBackUrl('list')
        ];

        $this->template->links = LinkHelper::createLinksFromArray($links);
    }

    protected function createComponentChangeStatusForm() {
        $statuses = [];
        foreach(ContainerStatus::getAll() as $key => $value) {
            if(!in_array($key, [ContainerStatus::RUNNING, ContainerStatus::NOT_RUNNING])) continue;

            $statuses[] = [
                'value' => $key,
                'text' => $value
            ];
        }

        $form = $this->componentFactory->getFormBuilder();

        $form->setAction($this->createURL('changeStatusFormSubmit'));
        
        $form->addSelect('status', 'Status:')
            ->setRequired()
            ->addRawOptions($statuses);

        $form->addHiddenInput('ids')
            ->setValue($this->httpRequest->get('ids'));

        $form->addSubmit('Change');

        return $form;
    }

    public function handleChangeStatusFormSubmit(FormRequest $fr) {
        try {
            $containerIds = explode(', ', $this->httpRequest->post('ids'));

            $this->app->contentRepository->beginTransaction(__METHOD__);

            $this->app->containerManager->bulkUpdateContainers($containerIds, [
                'status' => $fr->status
            ]);

            $this->app->contentRepository->commit($this->getUserId(), __METHOD__);

            $this->flashMessage('Successfully changed status for selected containers.', 'success');
        } catch(AException $e) {
            $this->app->contentRepository->rollback(__METHOD__);

            $this->flashMessage('Could not change status for selected containers. Reason: ' . $e->getMessage(), 'error', 10);
        }

        $this->redirect($this->createURL('list'));
    }
}

?>