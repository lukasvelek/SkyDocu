<?php

namespace App\Modules\AdminModule;

use App\Constants\Container\ReportRightEntityType;
use App\Constants\Container\ReportRightOperations;
use App\Constants\Container\SystemGroups;
use App\Core\DB\DatabaseRow;
use App\Core\Http\FormRequest;
use App\Exceptions\AException;
use App\Exceptions\GeneralException;
use App\Helpers\LinkHelper;
use App\Lib\Forms\Reducers\ProcessReportGrantRightFormReducer;
use App\UI\GridBuilder2\Action;
use App\UI\GridBuilder2\Cell;
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
            LinkBuilder::createSimpleLink('New report', $this->createURL('reportForm'), 'link')
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
                ->href($this->createURLString('reportForm', ['reportId' => $primaryKey]))
                ->text('Edit');

            return $el;
        };

        // RIGHTS
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

    public function handleDeleteForm() {
        $this->mandatoryUrlParams(['reportId'], $this->createURL('list'));
    }

    public function renderDeleteForm() {
        $links = [
            $this->createBackUrl('list')
        ];

        $this->template->links = LinkHelper::createLinksFromArray($links);
    }

    protected function createComponentDeleteReportForm() {
        $reportId = $this->httpRequest->get('reportId');
        $report = $this->processReportManager->getReportById($reportId);
        
        $form = $this->componentFactory->getFormBuilder();

        $form->setAction($this->createURL('deleteFormSubmit', ['reportId' => $reportId]));

        $form->addLabel('lbl_text1', 'Please type in the name of the report below (\'' . $report->title . '\'):');

        $form->addTextInput('reportName', 'Report name:')
            ->setRequired();

        $form->addSubmit('Delete');

        return $form;
    }

    public function handleDeleteFormSubmit(FormRequest $fr) {
        $this->mandatoryUrlParams(['reportId'], $this->createURL('list'));

        try {
            $reportId = $this->httpRequest->get('reportId');
            $report = $this->processReportManager->getReportById($reportId);

            if($fr->reportName != $report->title) {
                throw new GeneralException('Entered report name does not match the actual report name.');
            }

            $this->processReportsRepository->beginTransaction(__METHOD__);

            $this->processReportManager->deleteReport($reportId);

            $this->processReportsRepository->commit($this->getUserId(), __METHOD__);

            $this->flashMessage('Successfully deleted report.', 'success');
        } catch(AException $e) {
            $this->processReportsRepository->rollback(__METHOD__);

            $this->flashMessage('Could not delete report. Reason: ' . $e->getMessage(), 'error', 10);
        }

        $this->redirect($this->createURL('list'));
    }

    public function renderReportForm() {
        $this->template->links = $this->createBackUrl('list');

        if($this->httpRequest->get('reportId') === null) {
            $this->template->report_action = 'New';
        } else {
            $this->template->report_action = 'Edit';
        }
    }

    protected function createComponentReportForm() {
        $report = null;
        $params = [];
        
        if($this->httpRequest->get('reportId') !== null) {
            $report = $this->processReportManager->getReportById($this->httpRequest->get('reportId'));

            $params['reportId'] = $report->reportId;
        }

        $form = $this->componentFactory->getFormBuilder();

        $form->setAction($this->createURL('reportFormSubmit', $params));

        $title = $form->addTextInput('title', 'Title:')
            ->setRequired();

        if($report !== null) {
            $title->setValue($report->title);
        }

        $description = $form->addTextArea('description', 'Description:');

        if($report !== null) {
            $description->setContent($report->description);
        }

        $form->addSubmit('Save');

        return $form;
    }

    public function handleReportFormSubmit(FormRequest $fr) {
        $this->mandatoryUrlParams(['reportId'], $this->createURL('list'));
        
        $reportId = $this->httpRequest->get('reportId');
        $isNew = true;

        try {
            $this->processReportsRepository->beginTransaction(__METHOD__);

            if($reportId === null) {
                $reportId = $this->processReportManager->createNewReport(
                    $this->getUserId(),
                    $fr->title,
                    $fr->description
                );
            } else {
                $this->processReportManager->updateReport(
                    $reportId,
                    [
                        'title' => $fr->title,
                        'description' => $fr->description
                    ]
                );

                $isNew = false;
            }

            $this->processReportsRepository->commit($this->getUserId(), __METHOD__);

            if($isNew) {
                $this->flashMessage('Successfully created a new report. Please create a definition now.', 'success');
            } else {
                $this->flashMessage('Successfully updated the report. Please create a definition now.', 'success');
            }

            $this->redirect($this->createURL('reportDefinitionForm', ['reportId' => $reportId]));
        } catch(AException $e) {
            $this->processReportsRepository->rollback(__METHOD__);

            if($isNew) {
                $this->flashMessage('Could not create a new report. Reason: ' . $e->getMessage(), 'error', 10);
            } else {
                $this->flashMessage('Could not update the report. Reason: ' . $e->getMessage(), 'error', 10);
            }

            $this->redirect($this->createURL('list'));
        }
    }

    public function handleReportDefinitionForm() {
        $this->mandatoryUrlParams(['reportId'], $this->createURL('list'));
    }

    public function renderReportDefinitionForm() {
        $links = [];

        $this->template->links = LinkHelper::createLinksFromArray($links);
    }

    protected function createComponentReportDefinitionForm() {
        $reportId = $this->httpRequest->get('reportId');

        $report = $this->processReportManager->getReportById($reportId);

        $form = $this->componentFactory->getFormBuilder();

        $form->setAction($this->createURL('reportDefinitionFormSubmit', ['reportId' => $reportId]));

        $textArea = $form->addTextArea('definition', 'Definition:')
            ->setLines(20)
            ->setRequired();

        if($report->definition !== null) {
            $textArea->setContent(base64_decode($report->definition));
        }

        // If saving fails, the unsaved definition overwrites the current one
        if($this->httpRequest->get('definition') !== null) {
            $textArea->setContent(base64_decode($this->httpRequest->get('definition')));
        }

        $form->addSubmit('Save');

        $form->addButton('Live view')
            ->setOnClick('openLiveview()');

        if($this->httpRequest->get('definition') !== null) {
            $form->addButton('Reset to the last saved definition')
                ->setOnClick('resetToLastSavedDefinition()');

            $this->addScript('
                function resetToLastSavedDefinition() {
                    const url = "' . $this->createURLString('reportDefinitionForm', ['reportId' => $reportId]) . '";

                    const confirmResult = confirm("Are you sure you want to reset the unsaved definition to the saved one?");

                    if(confirmResult) {
                        location.href(url);
                    }
                }
            ');
        }

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
        $this->mandatoryUrlParams(['reportId'], $this->createURL('list'));

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

            $this->redirect($this->createURL('reportDefinitionForm', ['reportId' => $this->httpRequest->get('reportId'), 'definition' => base64_encode($fr->definition)]));
        }
    }

    public function handleLiveview() {
        $this->mandatoryUrlParams(['reportId'], $this->createURL('list'));
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
        $this->mandatoryUrlParams(['reportId'], $this->createURL('list'));

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

    public function handlePublish() {
        $this->mandatoryUrlParams(['reportId'], $this->createURL('list'));
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
        $this->mandatoryUrlParams(['reportId'], $this->createURL('list'));

        try {
            $this->processReportsRepository->beginTransaction(__METHOD__);

            $this->processReportManager->updateReport(
                $this->httpRequest->get('reportId'),
                [
                    'isEnabled' => 1
                ]
            );

            $this->processReportsRepository->commit($this->getUserId(), __METHOD__);

            $this->flashMessage('Successfully published report.', 'success');
        } catch(AException $e) {
            $this->processReportsRepository->rollback(__METHOD__);

            $this->flashMessage('Could not publish report. Reason: ' . $e->getMessage(), 'error');
        }

        $this->redirect($this->createURL('list'));
    }

    public function handleListRights() {
        $this->mandatoryUrlParams(['reportId'], $this->createURL('list'));
    }

    public function renderListRights() {
        $links = [
            $this->createBackUrl('list'),
            LinkBuilder::createSimpleLink('Grant right', $this->createURL('grantRightForm', ['reportId' => $this->httpRequest->get('reportId')]), 'link')
        ];

        $this->template->links = LinkHelper::createLinksFromArray($links);
    }

    protected function createComponentProcessReportRightsGrid() {
        $reportId = $this->httpRequest->get('reportId');

        $grid = $this->componentFactory->getGridBuilder($this->containerId);

        $qb = $this->processReportRightsRepository->composeQueryForReportRights();
        $qb->andWhere('reportId = ?', [$reportId])
            ->orderBy('dateCreated')
            ->orderBy('entityId');

        $grid->createDataSourceFromQueryBuilder($qb, 'rightId');
        $grid->addQueryDependency('reportId', $reportId);

        $col = $grid->addColumnText('entityId', 'Entity');
        $col->onRenderColumn[] = function(DatabaseRow $row, Row $_row, Cell $cell, HTML $html, mixed $value) {
            $el = HTML::el('span');

            if($row->entityType == ReportRightEntityType::USER) {
                // user

                try {
                    $user = $this->app->userManager->getUserById($value);

                    $el->text($user->getFullname());
                } catch(AException $e) {
                    $el->text('-')
                        ->title('Unknown user ID \'' . $value . '\'.');
                }
            } else {
                // group

                try {
                    $group = $this->groupManager->getGroupById($value);

                    $el->text(SystemGroups::toString($group->title));
                } catch(AException $e) {
                    $el->text('-')
                        ->title('Unknown group ID \'' . $value . '\'.');
                }
            }

            return $el;
        };

        $grid->addColumnConst('operation', 'Operation', ReportRightOperations::class);

        $revoke = $grid->addAction('revoke');
        $revoke->setTitle('Revoke');
        $revoke->onCanRender[] = function(DatabaseRow $row, Row $_row, Action &$action) {
            if($row->entityId == $this->getUserId()) {
                return false;
            }

            return true;
        };
        $revoke->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
            $el = HTML::el('a');

            $el->class('grid-link')
                ->href($this->createURLString('revokeRight', ['reportId' => $row->reportId, 'entityId' => $row->entityId, 'entityType' => $row->entityType, 'operation' => $row->operation]))
                ->text('Revoke');

            return $el;
        };

        return $grid;
    }

    public function handleRevokeRight() {
        $this->mandatoryUrlParams(['reportId', 'entityId', 'entityType', 'operation'], $this->createURL('list'));

        $reportId = $this->httpRequest->get('reportId');
        $entityId = $this->httpRequest->get('entityId');
        $entityType = $this->httpRequest->get('entityType');
        $operation = $this->httpRequest->get('operation');

        try {
            $this->processReportRightsRepository->beginTransaction(__METHOD__);
            
            $this->processReportManager->revokeReportRightToEntity($reportId, $entityId, $entityType, $operation);

            $this->processReportRightsRepository->commit($this->getUserId(), __METHOD__);
            
            $this->flashMessage(sprintf(
                'Successfully revoked operation right \'%s\' to %s \'%s\'.',
                ReportRightOperations::toString($operation),
                ($entityType == ReportRightEntityType::USER ? 'user' : 'group'),
                $entityId
            ), 'success');
        } catch(AException $e) {
            $this->processReportRightsRepository->rollback(__METHOD__);

            $this->flashMessage(sprintf(
                'Could not revoke operation right \'%s\' to %s \'%s\'. Reason: %s',
                ReportRightOperations::toString($operation),
                ($entityType == ReportRightEntityType::USER ? 'user' : 'group'),
                $entityId,
                $e->getMessage()
            ), 'error', 10);
        }

        $this->redirect('listRights', ['reportId' => $reportId]);
    }

    public function handleGrantRightForm() {
        $this->mandatoryUrlParams(['reportId'], $this->createURL('list'));
    }

    public function renderGrantRightForm() {
        $links = [
            $this->createBackUrl('listRights', ['reportId' => $this->httpRequest->get('reportId')])
        ];

        $this->template->links = LinkHelper::createLinksFromArray($links);
    }

    protected function createComponentGrantReportRightForm() {
        $reportId = $this->httpRequest->get('reportId');

        $form = $this->componentFactory->getFormBuilder();

        $form->setAction($this->createURL('grantRightFormSubmit', ['reportId' => $reportId]));

        $form->addSelect('entityType', 'Entity type:')
            ->addRawOption(ReportRightEntityType::USER, 'User')
            ->addRawOption(ReportRightEntityType::GROUP, 'Group');

        $form->addSelect('entityId', 'Entity:')
            ->setDisabled();

        $form->addSelect('operation', 'Operation:')
            ->setDisabled();

        $form->addSubmit('Grant');

        $form->setCallReducerOnChange();
        $form->reducer = new ProcessReportGrantRightFormReducer($this->app, $this->httpRequest);
        $form->reducer->setContainerId($this->containerId);

        return $form;
    }

    public function handleGrantRightFormSubmit(FormRequest $fr) {
        $reportId = $this->httpRequest->get('reportId');

        try {
            $this->processReportRightsRepository->beginTransaction(__METHOD__);

            $this->processReportManager->grantReportRightToEntity($reportId, $fr->entityId, $fr->entityType, $fr->operation);

            $this->processReportRightsRepository->commit($this->getUserId(), __METHOD__);

            $type = '';
            $name = '';
            if($fr->entityType == ReportRightEntityType::GROUP) {
                $group = $this->groupManager->getGroupById($fr->entityId);

                $name = SystemGroups::toString($group);
                $type = 'group';
            } else {
                $user = $this->app->userManager->getUserById($fr->entityId);

                $name = $user->getFullname();
                $type = 'user';
            }

            $this->flashMessage(sprintf('Successfully granted right for operation <b>%s</b> to %s %s.', ReportRightOperations::toString($fr->operation), $type, $name), 'success');
        } catch(AException $e) {
            $this->processReportRightsRepository->rollback(__METHOD__);

            $this->flashMessage('Could not grant right for operation. Reason: ' . $e->getMessage(), 'error', 10);
        }

        $this->redirect($this->createURL('listRights', ['reportId' => $reportId]));
    }
}