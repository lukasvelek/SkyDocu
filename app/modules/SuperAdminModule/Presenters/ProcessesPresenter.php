<?php

namespace App\Modules\SuperAdminModule;

use App\Constants\JobQueueTypes;
use App\Constants\ProcessStatus;
use App\Core\DB\DatabaseRow;
use App\Core\Http\FormRequest;
use App\Core\Http\HttpRequest;
use App\Exceptions\AException;
use App\Exceptions\GeneralException;
use App\Helpers\LinkHelper;
use App\Repositories\Container\ProcessRepository;
use App\UI\GridBuilder2\Action;
use App\UI\GridBuilder2\Row;
use App\UI\HTML\HTML;
use App\UI\LinkBuilder;

class ProcessesPresenter extends ASuperAdminPresenter {
    public function __construct() {
        parent::__construct('ProcessesPresenter', 'Processes');
    }

    public function renderList() {
        $links = [
            LinkBuilder::createSimpleLink('New process', $this->createFullURL('SuperAdmin:ProcessEditor', 'form'), 'link')
        ];

        $this->template->links = LinkHelper::createLinksFromArray($links);
    }

    protected function createComponentProcessesGrid(HttpRequest $request) {
        $grid = $this->componentFactory->getGridBuilder();

        $qb = $this->app->processRepository->composeQueryForProcesses();
        $qb->andWhere($qb->getColumnInValues('status', [ProcessStatus::NEW, ProcessStatus::IN_DISTRIBUTION]))
            ->orderBy('title', 'ASC')
            ->orderBy('version', 'ASC');

        $grid->createDataSourceFromQueryBuilder($qb, 'processId');

        $grid->addColumnText('title', 'Title');
        $grid->addColumnText('description', 'Description');
        $grid->addColumnUser('userId', 'Author');
        $grid->addColumnText('version', 'Version');
        $grid->addColumnConst('status', 'Status', ProcessStatus::class);

        $copy = $grid->addAction('copy');
        $copy->setTitle('Copy');
        $copy->onCanRender[] = function(DatabaseRow $row, Row $_row, Action &$action) {
            if($row->status != ProcessStatus::IN_DISTRIBUTION) {
                return false;
            }

            try {
                $nextVersion = $this->app->processManager->getNextVersionForProcessId($row->processId, true);

                if($nextVersion !== null && $nextVersion->getStatus() == ProcessStatus::NEW) {
                    return false;
                }
            } catch(AException $e) {}

            return true;
        };
        $copy->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
            $params = [
                'processId' => $primaryKey,
                'uniqueProcessId' => $row->uniqueProcessId
            ];

            $el = HTML::el('a');
            $el->text('Copy')
                ->class('grid-link')
                ->href($this->createURLString('copyProcess', $params));

            return $el;
        };

        $edit = $grid->addAction('edit');
        $edit->setTitle('Edit');
        $edit->onCanRender[] = function(DatabaseRow $row, Row $_row, Action &$action) {
            return $row->status == ProcessStatus::NEW;
        };
        $edit->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
            $params = [
                'processId' => $primaryKey,
                'uniqueProcessId' => $row->uniqueProcessId
            ];

            try {
                $previousProcess = $this->app->processManager->getPreviousVersionForProcessId($primaryKey);

                if($previousProcess !== null) {
                    $params['oldProcessId'] = $previousProcess->processId;
                }
            } catch(AException $e) {}

            $el = HTML::el('a');
            $el->text('Edit')
                ->class('grid-link')
                ->href($this->createFullURLString('SuperAdmin:ProcessEditor', 'form', $params));

            return $el;
        };

        $changeVisibility = $grid->addAction('changeVisibility');
        $changeVisibility->setTitle('Change visibility');
        $changeVisibility->onCanRender[] = function(DatabaseRow $row, Row $_row, Action &$action) {
            return $row->status == ProcessStatus::IN_DISTRIBUTION;
        };
        $changeVisibility->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
            $el = HTML::el('a');
            $el->text('Change visibility')
                ->class('grid-link')
                ->href($this->createURLString('changeVisibilityForm', ['processId' => $primaryKey]));

            return $el;
        };

        $delete = $grid->addAction('delete');
        $delete->setTitle('Delete');
        $delete->onCanRender[] = function() {
            return true;
        };
        $delete->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
            $el = HTML::el('a');
            $el->text('Delete')
                ->class('grid-link')
                ->href($this->createURLString('deleteForm', ['processId' => $primaryKey]));

            return $el;
        };

        return $grid;
    }

    public function handleDeleteForm(?FormRequest $fr = null) {
        if($fr !== null) {
            try {
                $this->app->processRepository->beginTransaction(__METHOD__);

                $process = $this->app->processManager->getProcessById($this->httpRequest->get('processId'));

                if($fr->title != $process->title || !$this->app->userAuth->authUser($fr->password)) {
                    throw new GeneralException('Bad credentials entered.');
                }

                $this->app->processManager->updateProcess($this->httpRequest->get('processId'), ['status' => ProcessStatus::NOT_IN_DISTRIBUTION]);

                $containers = $this->app->containerManager->getAllContainers(true, true);

                foreach($containers as $container) {
                    /**
                     * @var \App\Entities\ContainerEntity $container
                     */

                    if(!$container->isInDistribution()) continue;

                    $dbConn = $this->app->dbManager->getConnectionToDatabase($container->getDefaultDatabase()->getName());

                    $processRepository = new ProcessRepository($dbConn, $this->logger, $this->app->userRepository->transactionLogRepository);

                    $processRepository->removeCurrentDistributionProcessFromDistributionForUniqueProcessId($process->uniqueProcessId);
                }

                $this->app->processRepository->commit($this->getUserId(), __METHOD__);

                $this->flashMessage('Successfully deleted process.', 'success');
            } catch(AException $e) {
                $this->app->processRepository->rollback(__METHOD__);

                $this->flashMessage('Could not delete process. Reason: ' . $e->getMessage(), 'error', 10);
            }

            $this->redirect($this->createURL('list'));
        }
    }

    public function renderDeleteForm() {
        $this->template->links = $this->createBackUrl('list');
    }

    protected function createComponentDeleteProcessForm(HttpRequest $request) {
        $process = $this->app->processManager->getProcessById($request->get('processId'));

        $form = $this->componentFactory->getFormBuilder();

        $form->setAction($this->createURL('deleteForm', ['processId' => $request->get('processId')]));

        $form->addLabel('lbl_text1', 'Are you sure you want to delete process <b>' . $process->title . '</b>?');
        $form->addLabel('lbl_text2', 'If you are sure please enter your password and the process name below in order to authorize.');

        $form->addTextInput('title', 'Process name:')
            ->setRequired();

        $form->addPasswordInput('password', 'Your password:')
            ->setRequired();

        $form->addSubmit('Delete');

        return $form;
    }

    public function handleCopyProcess() {
        $processId = $this->httpRequest->get('processId');
        $uniqueProcessId = $this->httpRequest->get('uniqueProcessId');

        try {
            $this->app->processRepository->beginTransaction(__METHOD__);

            [$newProcessId, $uniqueProcessId] = $this->app->processManager->createNewProcessFromExisting($processId);

            $this->app->processRepository->commit($this->getUserId(), __METHOD__);

            $this->flashMessage('Successfully created a new copy of process.', 'success');
        } catch(AException $e) {
            $this->app->processRepository->rollback(__METHOD__);

            $this->flashMessage('Could not create a new copy of process. Reason: ' . $e->getMessage(), 'error', 10);
        }

        $this->redirect($this->createURL('list'));
    }

    public function renderChangeVisibilityForm() {
        $links = [
            $this->createBackUrl('list')
        ];

        $this->template->links = LinkHelper::createLinksFromArray($links);
    }

    protected function createComponentChangeVisibilityForm() {
        $processId = $this->httpRequest->get('processId');
        $process = $this->app->processManager->getProcessEntityById($processId);

        $possibleValues = [
            'Hidden',
            'Visible'
        ];

        $values = [];
        foreach($possibleValues as $k => $v) {
            $value = [
                'value' => $k,
                'text' => $v
            ];

            if(($process->isVisible() && $k == 1) ||
                (!$process->isVisible() && $k == 0)) {
                $value['selected'] = 'selected';
            }

            $values[] = $value;
        }

        $form = $this->componentFactory->getFormBuilder();

        $form->setAction($this->createURL('changeProcessVisibility', ['processId' => $processId]));

        $form->addSelect('visibility', 'Visibility:')
            ->addRawOptions($values);

        $form->addSubmit('Save');

        return $form;
    }

    public function handleChangeProcessVisibility(FormRequest $fr) {
        $processId = $this->httpRequest->get('processId');

        try {
            $this->app->processRepository->beginTransaction(__METHOD__);
            
            $data = [
                'isVisible' => $fr->visibility
            ];

            $this->app->processManager->updateProcess($processId, $data);

            $jobParams = [
                'processId' => $processId
            ];

            $this->app->jobQueueManager->insertNewJob(
                JobQueueTypes::CHANGE_PROCESS_VISIBILITY_IN_DISTRIBUTION,
                $jobParams,
                null
            );

            $this->app->processRepository->commit($this->getUserId(), __METHOD__);

            $this->flashMessage('Successfully changed process visibility. The change will be visible in containers shortly.', 'success');
        } catch(AException $e) {
            $this->app->processRepository->rollback(__METHOD__);

            $this->flashMessage('Could not change process visibility. Reason: ' . $e->getMessage(), 'error', 10);
        }

        $this->redirect($this->createURL('list'));
    }
}

?>