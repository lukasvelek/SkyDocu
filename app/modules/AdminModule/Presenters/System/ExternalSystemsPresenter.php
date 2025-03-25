<?php

namespace App\Modules\AdminModule;

use App\Core\DB\DatabaseRow;
use App\Core\Http\FormRequest;
use App\Core\Http\HttpRequest;
use App\Exceptions\AException;
use App\Exceptions\RequiredAttributeIsNotSetException;
use App\Helpers\LinkHelper;
use App\UI\GridBuilder2\Action;
use App\UI\GridBuilder2\Row;
use App\UI\HTML\HTML;
use App\UI\LinkBuilder;

class ExternalSystemsPresenter extends AAdminPresenter {
    public function __construct() {
        parent::__construct('ExternalSystemsPresenter', 'External systems');

        $this->setSystem();
    }

    public function renderList() {
        $links = [
            LinkBuilder::createSimpleLink('New external system', $this->createURL('newForm'), 'link')
        ];

        $this->template->links = LinkHelper::createLinksFromArray($links);
    }

    protected function createComponentContainerExternalSystemsGrid(HttpRequest $request) {
        $grid = $this->componentFactory->getGridBuilder($this->containerId);

        $grid->createDataSourceFromQueryBuilder($this->externalSystemsRepository->composeQueryForExternalSystems(), 'systemId');

        $grid->addColumnText('title', 'Title');
        $grid->addColumnBoolean('isEnabled', 'Enabled');

        $info = $grid->addAction('info');
        $info->setTitle('Information');
        $info->onCanRender[] = function() {
            return true;
        };
        $info->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
            $el = HTML::el('a');
            $el->href($this->createURLString('info', ['systemId' => $primaryKey]))
                ->class('grid-link')
                ->text('Information');

            return $el;
        };

        $log = $grid->addAction('log');
        $log->setTitle('Log');
        $log->onCanRender[] = function() {
            return true;
        };
        $log->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
            $el = HTML::el('a');
            $el->href($this->createURLString('log', ['systemId' => $primaryKey]))
                ->class('grid-link')
                ->text('Log');

            return $el;
        };

        $enable = $grid->addAction('enable');
        $enable->setTitle('Enable');
        $enable->onCanRender[] = function(DatabaseRow $row, Row $_row, Action &$action) {
            return ($row->isEnabled == false);
        };
        $enable->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
            $el = HTML::el('a');
            $el->href($this->createURLString('change', ['systemId' => $primaryKey, 'operation' => 'enable']))
                ->class('grid-link')
                ->text('Enable');

            return $el;
        };

        $disable = $grid->addAction('disable');
        $disable->setTitle('Disable');
        $disable->onCanRender[] = function(DatabaseRow $row, Row $_row, Action &$action) {
            return ($row->isEnabled == true);
        };
        $disable->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
            $el = HTML::el('a');
            $el->href($this->createURLString('change', ['systemId' => $primaryKey, 'operation' => 'disable']))
                ->class('grid-link')
                ->text('Disable');

            return $el;
        };

        return $grid;
    }

    public function handleNewForm(?FormRequest $fr = null) {
        if($fr !== null) {
            try {
                $this->externalSystemsRepository->beginTransaction(__METHOD__);

                $this->externalSystemsManager->createNewExternalSystem($fr->title, $fr->description, $this->getUserId());

                $this->externalSystemsRepository->commit($this->getUserId(), __METHOD__);

                $this->flashMessage('Successfully created new external system.', 'success');
            } catch(AException $e) {
                $this->externalSystemsRepository->rollback(__METHOD__);

                $this->flashMessage('Could not create a new external system. Reason: ' . $e->getMessage(), 'error', 10);
            }
            
            $this->redirect($this->createURL('list'));
        }
    }

    public function renderNewForm() {
        $this->template->links = $this->createBackUrl('list');
    }

    protected function createComponentNewExternalSystemForm(HttpRequest $request) {
        $form = $this->componentFactory->getFormBuilder();

        $form->setAction($this->createURL('newForm'));

        $form->addTextInput('title', 'Title:')
            ->setRequired();

        $form->addTextArea('description', 'Description:')
            ->setRequired();

        $form->addSubmit('Create');

        return $form;
    }

    public function renderLog() {

    }

    public function renderInfo() {

    }

    public function handleChange() {
        $systemId = $this->httpRequest->get('systemId');
        if($systemId === null) {
            throw new RequiredAttributeIsNotSetException('systemId');
        }
        $operation = $this->httpRequest->get('operation');

        try {
            $this->externalSystemsRepository->beginTransaction(__METHOD__);

            if($operation == 'enable') {
                $this->externalSystemsManager->enableExternalSystem($systemId);
            } else {
                $this->externalSystemsManager->disableExternalSystem($systemId);
            }

            $this->externalSystemsRepository->commit($this->getUserId(), __METHOD__);

            $this->flashMessage(sprintf('Successfully %s external system.', ($operation == 'enable' ? 'enabled' : 'disabled')), 'success');
        } catch(AException $e) {
            $this->externalSystemsRepository->rollback(__METHOD__);

            $this->flashMessage(sprintf('Could not %s external system. Reason: %s', ($operation == 'enable' ? 'enable' : 'disable'), $e->getMessage()), 'error', '10');
        }

        $this->redirect($this->createURL('list'));
    }
}

?>