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

    public function renderLiveview() {}

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

            $this->redirect($this->createURL('liveview', ['reportData' => $reportData]));
        } catch(AException $e) {
            $this->flashMessage('Could not open liveview for report. Reason: ' . $e->getMessage(), 'error', 10);

            $this->redirect($this->createURL('list'));
        }
    }
}