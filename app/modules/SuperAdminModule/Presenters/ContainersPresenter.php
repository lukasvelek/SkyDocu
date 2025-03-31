<?php

namespace App\Modules\SuperAdminModule;

use App\Constants\AuditLogObjectTypes;
use App\Constants\ContainerEnvironments;
use App\Constants\ContainerStatus;
use App\Core\DB\DatabaseRow;
use App\Core\Http\FormRequest;
use App\Core\Http\HttpRequest;
use App\Exceptions\AException;
use App\Exceptions\GeneralException;
use App\Helpers\GridHelper;
use App\Helpers\LinkHelper;
use App\UI\GridBuilder2\Action;
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

        $this->app->auditLogManager->createReadAuditLogEntry(null, $this->getUserId(), AuditLogObjectTypes::SUPERADMINISTRATION, AuditLogObjectTypes::SA_CONTAINER, null);
    }

    protected function createComponentContainersGrid(HttpRequest $request) {
        $grid = $this->componentFactory->getGridBuilder();

        $qb = $this->app->containerRepository->composeQueryForContainers();

        if(!$this->app->groupManager->isUserMemberOfContainerManagers($this->getUserId())) {
            $qb->andWhere('userId = ?', [$this->getUserId()]);
        }

        $grid->createDataSourceFromQueryBuilder($qb, 'containerId');
        $grid->setGridName(GridHelper::GRID_CONTAINERS);

        $grid->addColumnText('title', 'Title');
        $grid->addColumnConst('status', 'Status', ContainerStatus::class);
        $grid->addColumnConst('environment', 'Environment', ContainerEnvironments::class);

        $settings = $grid->addAction('settings');
        $settings->setTitle('Settings');
        $settings->onCanRender[] = function(DatabaseRow $row, Row $_row, Action &$action) {
            return $this->app->groupManager->isUserMemberOfContainerManagers($this->getUserId());
        };
        $settings->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
            $el = HTML::el('a')
                ->class('grid-link')
                ->href($this->createFullURLString('SuperAdmin:ContainerSettings', 'home', ['containerId' => $primaryKey]))
                ->text('Settings');

            return $el;
        };

        $approveRequest = $grid->addAction('approveRequest');
        $approveRequest->setTitle('Approve');
        $approveRequest->onCanRender[] = function(DatabaseRow $row, Row $_row, Action &$action) {
            return ($row->status == ContainerStatus::REQUESTED && $this->app->groupManager->isUserMemberOfContainerManagers($this->getUserId()));
        };
        $approveRequest->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
            $el = HTML::el('a')
                ->class('grid-link')
                ->href($this->createURLString('approveRequest', ['containerId' => $primaryKey]))
                ->text('Approve');

            return $el;
        };

        $declineRequest = $grid->addAction('declineRequest');
        $declineRequest->setTitle('Decline');
        $declineRequest->onCanRender[] = function(DatabaseRow $row, Row $_row, Action &$action) {
            return ($row->status == ContainerStatus::REQUESTED && $this->app->groupManager->isUserMemberOfContainerManagers($this->getUserId()));
        };
        $declineRequest->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
            $el = HTML::el('a')
                ->class('grid-link')
                ->href($this->createURLString('declineRequest', ['containerId' => $primaryKey]))
                ->text('Decline');

            return $el;
        };

        $grid->addQuickSearch('title', 'Title');
        
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
    
                $containerId = $this->app->containerManager->createNewContainer($fr->title, $fr->description, $this->getUserId(), (int)$fr->environment, $canShowReferent);

                $this->app->auditLogManager->createCreateAuditLogEntry(null, $this->getUserId(), AuditLogObjectTypes::SUPERADMINISTRATION, AuditLogObjectTypes::SA_CONTAINER, null);
    
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
        $environments = [];
        foreach(ContainerEnvironments::getAll() as $value => $text) {
            $environments[] = [
                'value' => $value,
                'text' => $text
            ];
        }

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

        $form->addSelect('environment', 'Environment:')
            ->addRawOptions($environments)
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

                $this->app->containerManager->createNewContainer($fr->title, $fr->description, $this->getUserId(), $fr->environment, $canShowReferent, ContainerStatus::REQUESTED);

                $this->app->auditLogManager->createCreateAuditLogEntry(null, $this->getUserId(), AuditLogObjectTypes::SUPERADMINISTRATION, AuditLogObjectTypes::SA_CONTAINER, AuditLogObjectTypes::REQUEST);

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
        $environments = [];
        foreach(ContainerEnvironments::getAll() as $value => $text) {
            $environments[] = [
                'value' => $value,
                'text' => $text
            ];
        }

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

        $form->addSelect('environment', 'Environment:')
            ->addRawOptions($environments)
            ->setRequired();

        $form->addCheckboxInput('canShowReferent', 'Is referent visible?')
            ->setChecked();

        $form->addSubmit('Submit');

        return $form;
    }

    public function handleApproveRequest() {
        $containerId = $this->httpRequest->get('containerId');

        try {
            $this->app->containerRepository->beginTransaction(__METHOD__);

            $this->app->containerManager->approveContainerRequest($containerId, $this->getUserId());

            $this->app->containerRepository->commit($this->getUserId(), __METHOD__);

            $this->flashMessage('Container request approved. Container will be created asynchronously.', 'success');
        } catch(AException $e) {
            $this->app->containerRepository->rollback(__METHOD__);

            $this->flashMessage('Could not approve container request. Reason: ' . $e->getMessage(), 'error');
        }

        $this->redirect($this->createURL('list'));
    }

    public function handleDeclineRequest() {
        $containerId = $this->httpRequest->get('containerId');

        try {
            $this->app->containerRepository->beginTransaction(__METHOD__);

            $this->app->containerManager->declineContainerRequest($containerId);

            $this->app->containerRepository->commit($this->getUserId(), __METHOD__);

            $this->flashMessage('Container request approved. Container will be created asynchronously.', 'success');
        } catch(AException $e) {
            $this->app->containerRepository->rollback(__METHOD__);

            $this->flashMessage('Could not approve container request. Reason: ' . $e->getMessage(), 'error');
        }

        $this->redirect($this->createURL('list'));
    }
}

?>