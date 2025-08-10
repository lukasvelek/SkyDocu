<?php

namespace App\Modules\AdminModule;

use App\Core\DB\DatabaseRow;
use App\Core\Http\FormRequest;
use App\Exceptions\AException;
use App\Helpers\LinkHelper;
use App\UI\GridBuilder2\Action;
use App\UI\GridBuilder2\JSON2GB;
use App\UI\GridBuilder2\Row;
use App\UI\HTML\HTML;
use App\UI\LinkBuilder;

class ProcessReportsPresenter extends AAdminPresenter {
    public function __construct() {
        parent::__construct('ProcessReportsPresenter', 'Process reports');

        $this->setProcesses();
    }

    public function renderList() {
        $links = [
            LinkBuilder::createSimpleLink('New report', $this->createURL('newReportForm'), 'link')
        ];

        $this->template->links = LinkHelper::createLinksFromArray($links);
    }

    protected function createComponentProcessReportsGrid() {
        $grid = $this->componentFactory->getGridBuilder($this->containerId);

        $qb = $this->processReportManager->composeQueryForAllVisibleReports($this->getUserId(), false);

        $grid->createDataSourceFromQueryBuilder($qb, 'reportId');

        $grid->addColumnText('title', 'Title');
        $grid->addColumnDatetime('dateCreated', 'Date created');
        $grid->addColumnBoolean('isEnabled', 'Published');

        // LIVE VIEW
        $liveview = $grid->addAction('liveview');
        $liveview->setTitle('Live view');
        $liveview->onCanRender[] = function(DatabaseRow $row, Row $_row, Action &$action) {
            if($this->supervisorAuthorizator->canUserViewAllReports($this->getUserId())) {
                return true;
            }

            if($this->containerProcessAuthorizator->canUserReadProcessReport($this->getUserId(), $row->reportId)) {
                return true;
            }

            return false;
        };
        $liveview->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
            $el = HTML::el('a');

            $el->class('grid-link')
                ->href($this->createURLString('liveview2', ['reportId' => $primaryKey]))
                ->text('Live view');

            return $el;
        };

        // PUBLISH
        $publish = $grid->addAction('publish');
        $publish->setTitle('Publish');
        $publish->onCanRender[] = function(DatabaseRow $row, Row $_row, Action &$action) {
            if($row->isEnabled == true) {
                return false;
            }

            if(!$this->containerProcessAuthorizator->canUserEditProcessReport($this->getUserId(), $row->reportId)) {
                return false;
            }

            return true;
        };
        $publish->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
            $el = HTML::el('a');

            $el->class('grid-link')
                ->href($this->createURLString('publish', ['reportId' => $primaryKey]))
                ->text('Publish');

            return $el;
        };

        // EDIT
        $edit = $grid->addAction('edit');
        $edit->setTitle('Edit');
        $edit->onCanRender[] = function(DatabaseRow $row, Row $_row, Action &$action) {
            if(!$this->containerProcessAuthorizator->canUserEditProcessReport($this->getUserId(), $row->reportId)) {
                return false;
            }

            return true;
        };
        $edit->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
            $el = HTML::el('a');

            $el->class('grid-link')
                ->href($this->createURLString('editReportForm', ['reportId' => $primaryKey]))
                ->text('Edit');

            return $el;
        };

        // EDIT RIGHTS
        $rights = $grid->addAction('rights');
        $rights->setTitle('Rights');
        $rights->onCanRender[] = function(DatabaseRow $row, Row $_row, Action &$action) {
            if(!$this->containerProcessAuthorizator->canUserGrantProcessReport($this->getUserId(), $row->reportId)) {
                return false;
            }

            return true;
        };
        $rights->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
            $el = HTML::el('a');

            $el->class('grid-link')
                ->href($this->createURLString('listRights', ['reportId' => $primaryKey]))
                ->text('Rights');

            return $el;
        };

        // DELETE
        $delete = $grid->addAction('delete');
        $delete->setTitle('Delete');
        $delete->onCanRender[] = function(DatabaseRow $row, Row $_row, Action &$action) {
            if(!$this->containerProcessAuthorizator->canUserDeleteProcessReport($this->getUserId(), $row->reportId)) {
                return false;
            }

            return true;
        };
        $delete->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
            $el = HTML::el('a');

            $el->class('grid-link')
                ->href($this->createURLString('deleteForm', ['reportId' => $primaryKey]))
                ->text('Delete');

            return $el;
        };

        return $grid;
    }

    public function renderNewReportForm() {
        $this->template->links = $this->createBackUrl('list');
    }

    protected function createComponentNewReportForm() {
        $form = $this->componentFactory->getFormBuilder();

        $form->setAction($this->createURL('newReportFormSubmit'));

        $form->addTextInput('title', 'Title:')
            ->setRequired();

        $form->addTextArea('description', 'Description:');

        $form->addSubmit('Save');

        return $form;
    }

    public function handleNewReportFormSubmit(FormRequest $fr) {
        try {
            $this->processReportsRepository->beginTransaction(__METHOD__);

            $reportId = $this->processReportManager->createNewReport(
                $this->getUserId(),
                $fr->title,
                $fr->description
            );

            $this->processReportsRepository->commit($this->getUserId(), __METHOD__);

            $this->flashMessage('Successfully created a new report. Please create a definition now.', 'success');

            $this->redirect($this->createURL('reportDefinitionForm', ['reportId' => $reportId]));
        } catch(AException $e) {
            $this->processReportsRepository->rollback(__METHOD__);

            $this->flashMessage('Could not create a new report. Reason: ' . $e->getMessage(), 'error', 10);

            $this->redirect($this->createURL('list'));
        }
    }

    public function renderReportDefinitionForm() {
        $links = [];

        $this->template->links = LinkHelper::createLinksFromArray($links);
    }

    protected function createComponentReportDefinitionForm() {
        $form = $this->componentFactory->getFormBuilder();

        $form->setAction($this->createURL('reportDefinitionFormSubmit', ['reportId' => $this->httpRequest->get('reportId')]));

        $textArea = $form->addTextArea('definition', 'Definition:')
            ->setLines(20)
            ->setRequired();

        if($this->httpRequest->get('definition') !== null) {
            $textArea->setContent($this->httpRequest->get('definition'));
        }

        $form->addSubmit('Save');

        $form->addButton('Live view')
            ->setOnClick('openLiveview()');

        $this->addScript('
            function openLiveview() {
                const definition = $("#definition").val();

                const b64 = btoa(definition);

                const url = "?page=Admin:ProcessReports&action=liveview&reportData=" + b64;

                open(url, "_blank");
            }
        ');

        return $form;
    }

    public function handleReportDefinitionFormSubmit(FormRequest $fr) {
        try {
            $this->processReportsRepository->beginTransaction(__METHOD__);

            $data = [
                'definition' => base64_encode($fr->definition)
            ];

            $this->processReportManager->updateReport($this->httpRequest->get('reportId'), $data);

            $this->processReportsRepository->commit($this->getUserId(), __METHOD__);

            $this->flashMessage('Successfully saved the definition for the report.', 'success');

            $this->redirect($this->createURL('list'));
        } catch(AException $e) {
            $this->processReportsRepository->rollback(__METHOD__);

            $this->flashMessage('Could not save the definition for the report. Reason: ' . $e->getMessage(), 'error', 10);

            $this->redirect($this->createURL('reportDefinitionForm', ['reportId' => $this->httpRequest->get('reportId'), 'definition' => $fr->definition]));
        }
    }

    public function renderLiveview() {
        $links = [];

        if($this->httpRequest->get('backUrl') !== null) {
            $backUrl = json_decode(base64_decode($this->httpRequest->get('backUrl')), true);

            $links[] = LinkBuilder::createSimpleLink('&larr; Back', $backUrl, 'link');
        }

        $this->template->links = LinkHelper::createLinksFromArray($links);
    }

    protected function createComponentProcessReportLiveViewGrid() {
        $data = $this->httpRequest->get('reportData');

        $data = json_decode(base64_decode($data), true);

        $helper = new JSON2GB(
            $this->componentFactory->getGridBuilder($this->containerId),
            $data,
            $this->groupManager,
            $this->app
        );

        $gb = $helper->getGridBuilder();

        return $gb;
    }

    public function handleLiveview2() {
        $reportId = $this->httpRequest->get('reportId');

        try {
            $report = $this->processReportManager->getReportById($reportId);

            $reportData = $report->definition;

            $this->redirect($this->createURL('liveview', [
                'reportData' => $reportData,
                'backUrl' => base64_encode(json_encode($this->createUrl('list')))
            ]));
        } catch(AException $e) {
            $this->flashMessage('Could not open liveview for report. Reason: ' . $e->getMessage(), 'error', 10);

            $this->redirect($this->createURL('list'));
        }
    }

    public function renderPublish() {}

    protected function createComponentPublishReportForm() {
        $report = $this->processReportManager->getReportById($this->httpRequest->get('reportId'));

        $form = $this->componentFactory->getFormBuilder();

        $form->setAction($this->createURL('publishFormSubmit', ['reportId' => $this->httpRequest->get('reportId')]));

        $form->addLabel('lbl_text1', 'Are you sure you want to publish report <b>' . $report->title . '</b>?');

        $form->addSubmit('Publish');
        $form->addButton('Go back')
            ->setOnClick('location.href = \'' . $this->createURLString('list') . '\';');

        return $form;
    }

    public function handlePublishFormSubmit(FormRequest $fr) {

    }
}