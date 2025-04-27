<?php

namespace App\Modules\AdminModule;

use App\Constants\Container\CustomMetadataTypes;
use App\Core\DB\DatabaseRow;
use App\Core\Http\FormRequest;
use App\Core\Http\HttpRequest;
use App\Exceptions\AException;
use App\Helpers\LinkHelper;
use App\UI\GridBuilder2\Row;
use App\UI\HTML\HTML;
use App\UI\LinkBuilder;

class ProcessMetadataPresenter extends AAdminPresenter {
    public function __construct() {
        parent::__construct('ProcessMetadataPresenter', 'Process metadata');

        $this->setProcesses();
    }

    public function renderList() {
        $this->template->links = $this->createBackFullUrl('Admin:Processes', 'list');

        $process = $this->processManager->getLastProcessForUniqueProcessId($this->httpRequest->get('uniqueProcessId'));

        $this->template->process_title = $process->title;
    }

    protected function createComponentProcessMetadataGrid(HttpRequest $request) {
        $grid = $this->componentFactory->getGridBuilder($this->containerId);

        $uniqueProcessId = $request->get('uniqueProcessId');

        $qb = $this->processMetadataRepository->composeQueryForProcessMetadata($uniqueProcessId);

        $grid->createDataSourceFromQueryBuilder($qb, 'metadataId');
        $grid->addQueryDependency('uniqueProcessId', $uniqueProcessId);

        $grid->addColumnText('title', 'Name');
        $grid->addColumnText('guiTitle', 'Title');
        $grid->addColumnConst('type', 'Type', CustomMetadataTypes::class);
        $grid->addColumnBoolean('isRequired', 'Required');
        $grid->addColumnBoolean('isSystem', 'System');

        $values = $grid->addAction('values');
        $values->setTitle('Values');
        $values->onCanRender[] = function() {
            return true;
        };
        $values->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) use ($uniqueProcessId) {
            $el = HTML::el('a');

            $el->href($this->createURLString('valueList', ['uniqueProcessId' => $uniqueProcessId, 'metadataId' => $primaryKey]))
                ->class('grid-link')
                ->text('Values');

            return $el;
        };

        return $grid;
    }

    public function renderValueList() {
        $process = $this->processManager->getLastProcessForUniqueProcessId($this->httpRequest->get('uniqueProcessId'));

        $this->template->process_title = $process->title;

        $metadata = $this->processMetadataManager->getProcessMetadataById($this->httpRequest->get('metadataId'));

        $this->template->metadata_title = $metadata->guiTitle;

        $links = [
            $this->createBackUrl('list', ['uniqueProcessId' => $this->httpRequest->get('uniqueProcessId')]),
            LinkBuilder::createSimpleLink('New value', $this->createURL('valueForm', [
                'uniqueProcessId' => $this->httpRequest->get('uniqueProcessId'),
                'metadataId' => $this->httpRequest->get('metadataId')
            ]), 'link')
        ];

        $this->template->links = LinkHelper::createLinksFromArray($links);
    }

    protected function createComponentProcessMetadataValuesGrid(HttpRequest $request) {
        $grid = $this->componentFactory->getGridBuilder($this->containerId);

        $qb = $this->processMetadataRepository->composeQueryForProcessMetadataValues($request->get('metadataId'));
        $qb->orderBy('sortingKey');

        $grid->createDataSourceFromQueryBuilder($qb, 'valueId');
        $grid->addQueryDependency('metadataId', $request->get('metadataId'));
        $grid->addQueryDependency('uniqueProcessId', $request->get('uniqueProcessId'));

        $grid->addColumnText('title', 'Title');
        $grid->addColumnText('metadataKey', 'Key');
        $grid->addColumnText('sortingKey', 'Sorting key');

        return $grid;
    }

    public function handleValueForm(?FormRequest $fr = null) {
        if($fr !== null) {
            try {
                $this->processMetadataRepository->beginTransaction(__METHOD__);

                $data = [
                    'metadataId' => $this->httpRequest->get('metadataId'),
                    'metadataKey' => $fr->key,
                    'sortingKey' => $fr->sortingKey,
                    'title' => $fr->title
                ];

                $this->processMetadataManager->addNewMetadataValue($data);

                $this->processMetadataRepository->commit($this->getUserId(), __METHOD__);

                $this->flashMessage('Successfully added new metadata value.', 'success');
            } catch(AException $e) {
                $this->processMetadataRepository->rollback(__METHOD__);

                $this->flashMessage('Could not add new metadata value. Reason: ' . $e->getMessage(), 'error', 10);
            }

            $this->redirect($this->createURL('valueList', ['uniqueProcessId' => $this->httpRequest->get('uniqueProcessId'), 'metadataId' => $this->httpRequest->get('metadataId')]));
        }
    }

    public function renderValueForm() {
        $process = $this->processManager->getLastProcessForUniqueProcessId($this->httpRequest->get('uniqueProcessId'));

        $this->template->process_title = $process->title;

        $metadata = $this->processMetadataManager->getProcessMetadataById($this->httpRequest->get('metadataId'));

        $this->template->metadata_title = $metadata->guiTitle;

        $this->template->links = $this->createBackUrl('valueList', ['uniqueProcessId' => $this->httpRequest->get('uniqueProcessId'), 'metadataId' => $this->httpRequest->get('metadataId')]);
    }

    protected function createComponentMetadataValueForm(HttpRequest $request) {
        $form = $this->componentFactory->getFormBuilder();

        $form->setAction($this->createURL('valueForm', ['uniqueProcessId' => $this->httpRequest->get('uniqueProcessId'), 'metadataId' => $this->httpRequest->get('metadataId')]));

        $form->addTextInput('title', 'Title:')
            ->setRequired();

        $form->addTextInput('key', 'Key:')
            ->setRequired();

        $form->addTextInput('sortingKey', 'Sorting key:')
            ->setRequired();

        $form->addSubmit('Create');

        return $form;
    }
}

?>