<?php

namespace App\Modules\SuperAdminModule;

use App\Constants\ProcessStatus;
use App\Core\DB\DatabaseRow;
use App\Core\Http\FormRequest;
use App\Core\Http\HttpRequest;
use App\Exceptions\AException;
use App\Exceptions\GeneralException;
use App\Helpers\LinkHelper;
use App\Repositories\Container\ProcessRepository;
use App\UI\FormBuilder2\JSON2FB;
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
        $qb->andWhere($qb->getColumnInValues('status', [ProcessStatus::NEW, ProcessStatus::IN_DISTRIBUTION]));

        $grid->createDataSourceFromQueryBuilder($qb, 'processId');

        $grid->addColumnText('title', 'Title');
        $grid->addColumnText('description', 'Description');
        $grid->addColumnUser('userId', 'Author');
        $grid->addColumnText('version', 'Version');
        $grid->addColumnConst('status', 'Status', ProcessStatus::class);

        $viewForm = $grid->addAction('viewForm');
        $viewForm->setTitle('View form');
        $viewForm->onCanRender[] = function() {
            return true;
        };
        $viewForm->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
            $el = HTML::el('a');
            $el->text('View form')
                ->class('grid-link')
                ->href($this->createURLString('viewForm', ['processId' => $primaryKey]));

            return $el;
        };

        $edit = $grid->addAction('edit');
        $edit->setTitle('Edit');
        $edit->onCanRender[] = function(DatabaseRow $row, Row $_row, Action &$action) {
            return true;
        };
        $edit->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
            $el = HTML::el('a');
            $el->text('Edit')
                ->class('grid-link')
                ->href($this->createFullURLString('SuperAdmin:ProcessEditor', 'form', ['processId' => $primaryKey, 'uniqueProcessId' => $row->uniqueProcessId]));

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

    public function renderViewForm() {
        $process = $this->app->processManager->getProcessById($this->httpRequest->get('processId'));

        $form = base64_decode($process->form);
        $form = new JSON2FB($this->componentFactory->getFormBuilder(), json_decode($form, true), null);
        $form->setViewOnly();

        $this->template->process_form = $form->render();
        $this->template->links = $this->createBackUrl('list');
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
}

?>