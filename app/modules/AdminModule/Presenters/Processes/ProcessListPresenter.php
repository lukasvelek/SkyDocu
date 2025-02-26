<?php

namespace App\Modules\AdminModule;

use App\Constants\Container\CustomMetadataTypes;
use App\Constants\Container\StandaloneProcesses;
use App\Core\DB\DatabaseRow;
use App\Core\Http\FormRequest;
use App\Core\Http\HttpRequest;
use App\Exceptions\AException;
use App\Exceptions\RequiredAttributeIsNotSetException;
use App\UI\GridBuilder2\Action;
use App\UI\GridBuilder2\Cell;
use App\UI\GridBuilder2\Row;
use App\UI\HTML\HTML;
use App\UI\LinkBuilder;

class ProcessListPresenter extends AAdminPresenter {
    public function __construct() {
        parent::__construct('ProcessListPresenter', 'Process list');

        $this->setProcesses();
    }

    public function renderList() {}

    protected function createComponentProcessGrid() {
        $grid = $this->componentFactory->getGridBuilder($this->containerId);

        $qb = $this->processRepository->composeQueryForProcessTypes();

        $grid->createDataSourceFromQueryBuilder($qb, 'typeId');

        $col = $grid->addColumnText('typeKey', 'Title');
        $col->onRenderColumn[] = function(DatabaseRow $row, Row $_row, Cell $cell, HTML $html, mixed $value) {
            return $row->title;
        };

        $grid->addColumnText('description', 'Description');
        $grid->addColumnBoolean('isEnabled', 'Enabled');

        $grid->addFilter('typeKey', null, StandaloneProcesses::getAll());
        $grid->addFilter('isEnabled', null, ['No', 'Yes']);

        $metadata = $grid->addAction('metadata');
        $metadata->setTitle('Metadata');
        $metadata->onCanRender[] = function(DatabaseRow $row, Row $_row, Action &$action) {
            return !empty($this->standaloneProcessManager->getProcessMetadataForProcess($row->typeId));
        };
        $metadata->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
            $el = HTML::el('a')
                ->class('grid-link')
                ->text('Metadata')
                ->href($this->createURLString('metadataList', ['typeId' => $primaryKey]));

            return $el;
        };

        $switch = $grid->addAction('switch');
        $switch->setTitle('Enable / Disable');
        $switch->onCanRender[] = function() {
            return true;
        };
        $switch->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
            $el = HTML::el('a');
            $el->class('grid-link');

            $url = [
                'type' => $row->typeKey
            ];

            if($row->isEnabled == true) {
                $el->text('Disable');
                $url['operation'] = 'disable';
            } else {
                $el->text('Enable');
                $url['operation'] = 'enable';
            }

            $el->href($this->createURLString('switch', $url));

            return $el;
        };

        return $grid;
    }

    public function handleSwitch() {
        $type = $this->httpRequest->get('type');
        if($type === null) {
            throw new RequiredAttributeIsNotSetException('type');
        }
        $operation = $this->httpRequest->get('operation');
        if($operation === null) {
            throw new RequiredAttributeIsNotSetException('operation');
        }

        $data = [];
        if($operation == 'enable') {
            $data['isEnabled'] = '1';
        } else {
            $data['isEnabled'] = '0';
        }

        try {
            $this->processRepository->beginTransaction(__METHOD__);

            $this->standaloneProcessManager->updateProcessType($type, $data);

            $this->processRepository->commit($this->getUserId(), __METHOD__);

            $this->flashMessage('Process ' . ($operation == 'enable' ? 'enabled' : 'disabled') . ' successfully.', 'success');
        } catch(AException $e) {
            $this->processRepository->rollback(__METHOD__);

            $this->flashMessage('Could not ' . ($operation == 'enable' ? 'enable' : 'disable')  . ' process. Reason: ' . $e->getMessage(), 'error', 10);
        }

        $this->redirect($this->createURL('list'));
    }

    public function renderMetadataList() {
        $this->template->links = $this->createBackUrl('list', ['typeId' => $this->httpRequest->get('typeId')]);
    }

    protected function createComponentProcessMetadataGrid(HttpRequest $request) {
        $typeId = $request->get('typeId');

        $grid = $this->componentFactory->getGridBuilder($this->containerId);

        $grid->createDataSourceFromQueryBuilder($this->standaloneProcessManager->composeQueryForProcessMetadataForProcess($typeId), 'metadataId');
        $grid->addQueryDependency('typeId', $typeId);

        $grid->addColumnText('title', 'Title');
        $grid->addColumnText('guiTitle', 'GUI title');
        $grid->addColumnConst('type', 'Type', CustomMetadataTypes::class);
        $grid->addColumnBoolean('isRequired', 'Is required');

        $enumList = $grid->addAction('enumList');
        $enumList->setTitle('Values');
        $enumList->onCanRender[] = function(DatabaseRow $row, Row $_row) {
            return $row->type == CustomMetadataTypes::ENUM;
        };
        $enumList->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) use ($typeId) {
            $el = HTML::el('a')
                ->text('Values')
                ->href($this->createURLString('metadataEnumList', ['metadataId' => $primaryKey, 'typeId' => $typeId]))
                ->class('grid-link');

            return $el;
        };

        return $grid;
    }

    public function handleMetadataEnumList() {
        $metadataId = $this->httpRequest->get('metadataId');
        $typeId = $this->httpRequest->get('typeId');

        $links = [
            $this->createBackUrl('metadataList', ['typeId' => $typeId]),
            LinkBuilder::createSimpleLink('New value', $this->createURL('newEnumValueForm', ['metadataId' => $metadataId, 'typeId' => $typeId]), 'link')
        ];

        $this->saveToPresenterCache('links', implode('&nbsp;&nbsp;', $links));
    }

    public function renderMetadataEnumList() {
        $this->template->links = $this->loadFromPresenterCache('links');
    }

    protected function createComponentProcessMetadataEnumGrid(HttpRequest $request) {
        $metadataId = $request->get('metadataId');
        $typeId = $request->get('typeId');

        $grid = $this->componentFactory->getGridBuilder($this->containerId);

        $qb = $this->standaloneProcessManager->composeQueryForProcessMetadataEnumForMetadata($metadataId)
            ->orderBy('title');

        $grid->createDataSourceFromQueryBuilder($qb, 'valueId');
        $grid->addQueryDependency('metadataId', $metadataId);

        $grid->addColumnText('title', 'Title');

        $edit = $grid->addAction('edit');
        $edit->setTitle('Edit');
        $edit->onCanRender[] = function() {
            return true;
        };
        $edit->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) use ($metadataId, $typeId) {
            $el = HTML::el('a')
                ->class('grid-link')
                ->href($this->createURLString('editEnumValueForm', ['valueId' => $primaryKey, 'metadataId' => $metadataId, 'typeId' => $typeId]))
                ->text('Edit');

            return $el;
        };

        return $grid;
    }

    public function handleNewEnumValueForm(?FormRequest $fr = null) {
        $metadataId = $this->httpRequest->get('metadataId');
        $typeId = $this->httpRequest->get('typeId');

        if($fr !== null) {
            try {
                $this->standaloneProcessManager->processManager->processRepository->beginTransaction(__METHOD__);

                $this->standaloneProcessManager->createMetadataEnumValue($metadataId, $fr->title);

                $this->standaloneProcessManager->processManager->processRepository->commit($this->getUserId(), __METHOD__);

                $this->flashMessage('New metadata value created.', 'success');
            } catch(AException $e) {
                $this->standaloneProcessManager->processManager->processRepository->rollback(__METHOD__);

                $this->flashMessage('Could not create new metadata value. Reason: ' . $e->getMessage(), 'error', 10);
            }

            $this->redirect($this->createURL('metadataEnumList', ['metadataId' => $metadataId, 'typeId' => $typeId]));
        }
    }

    public function renderNewEnumValueForm() {
        $this->template->links = $this->createBackUrl('metadataEnumList', ['metadataId' => $this->httpRequest->get('metadataId'), 'typeId' => $this->httpRequest->get('typeId')], 'link');
    }

    protected function createComponentNewMetadataEnumValueForm(HttpRequest $request) {
        $form = $this->componentFactory->getFormBuilder();

        $form->setAction($this->createURL('newEnumValueForm', ['metadataId' => $request->get('metadataId'), 'typeId' => $request->get('typeId')]));

        $form->addTextInput('title', 'Title:')
            ->setRequired();

        $form->addSubmit('Create');

        return $form;
    }

    public function handleEditEnumValueForm(?FormRequest $fr = null) {
        $metadataId = $this->httpRequest->get('metadataId');
        $typeId = $this->httpRequest->get('typeId');
        $valueId = $this->httpRequest->get('valueId');

        if($fr !== null) {
            try {
                $this->processRepository->beginTransaction(__METHOD__);

                $this->standaloneProcessManager->updateMetadataEnumValue($valueId, $fr->title);

                $this->processRepository->commit($this->getUserId(), __METHOD__);

                $this->flashMessage('Metadata enum value updated.', 'success');
            } catch(AException $e) {
                $this->processRepository->rollback(__METHOD__);

                $this->flashMessage('Could not update metadata enum value. Reason: ' . $e->getMessage(), 'error', 10);
            }

            $this->redirect($this->createURL('metadataEnumList', ['metadataId' => $metadataId, 'typeId' => $typeId]));
        }
    }

    public function renderEditEnumValueForm() {
        $this->template->links = $this->createBackUrl('metadataEnumList', ['metadataId' => $this->httpRequest->get('metadataId'), 'typeId' => $this->httpRequest->get('typeId')], 'link');
    }

    protected function createComponentEditMetadataEnumValueForm(HttpRequest $request) {
        $value = $this->standaloneProcessManager->getMetadataEnumValueById($request->get('valueId'));

        $form = $this->componentFactory->getFormBuilder();

        $form->setAction($this->createURL('editEnumValueForm', ['metadataId' => $request->get('metadataId'), 'typeId' => $request->get('typeId'), 'valueId' => $request->get('valueId')]));

        $form->addTextInput('title', 'Title:')
            ->setRequired()
            ->setValue($value->title);

        $form->addSubmit('Save');

        return $form;
    }
}

?>