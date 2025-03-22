<?php

namespace App\Modules\UserModule;

use App\Components\ProcessesGrid\ProcessesGrid;
use App\Components\ProcessViewSidebar\ProcessViewSidebar;
use App\Constants\Container\ProcessFormValues\HomeOffice;
use App\Constants\Container\ProcessGridViews;
use App\Constants\Container\ProcessStatus;
use App\Constants\Container\StandaloneProcesses;
use App\Constants\Container\SystemProcessTypes;
use App\Core\DB\DatabaseRow;
use App\Core\Http\FormRequest;
use App\Core\Http\HttpRequest;
use App\Exceptions\AException;
use App\Exceptions\RequiredAttributeIsNotSetException;
use App\Helpers\DateTimeFormatHelper;
use App\Helpers\LinkHelper;
use App\Helpers\ProcessHelper;
use App\UI\HTML\HTML;
use App\UI\LinkBuilder;

class ProcessesPresenter extends AUserPresenter {
    public function __construct() {
        parent::__construct('ProcessesPresenter', 'Processes');
    }

    public function handleList() {
        if($this->httpRequest->get('view') === null && $this->httpRequest->post('view') === null) {
            $this->redirect($this->createURL('list', ['view' => ProcessGridViews::VIEW_ALL]));
        }
    }

    public function renderList() {
        $this->template->links = [];
    }

    protected function createComponentProcessViewsSidebar(HttpRequest $request) {
        $sidebar = new ProcessViewSidebar($request, $this->supervisorAuthorizator, $this->getUserId());

        return $sidebar;
    }

    protected function createComponentProcessesGrid(HttpRequest $request) {
        $grid = new ProcessesGrid(
            $this->componentFactory->getGridBuilder($this->containerId),
            $this->app,
            $this->gridManager,
            $this->processManager,
            $this->documentManager
        );

        if($this->httpRequest->post('view') !== null) {
            $grid->setView($request->post('view'));
        } else if($this->httpRequest->get('view') !== null) {
            $grid->setView($request->get('view'));
        }
    
        return $grid;
    }

    public function handleProfile() {
        $processId = $this->httpRequest->get('processId');
        if($processId === null) {
            throw new RequiredAttributeIsNotSetException('processId');
        }
        $backView = $this->httpRequest->get('backView');

        try {
            $process = $this->processManager->getProcessById($processId);
        } catch(AException $e) {
            $this->flashMessage('Process not found. Reason: ' . $e->getMessage(), 'error', 10);

            $params = [];
            if($backView !== null) {
                $params['view'] = $backView;
            }
            $this->redirect($this->createURL('list', $params));
        }

        // BASIC INFORMATION
        $basicInformationCode = '';
        $createRow = function(string $title, mixed $data) use (&$basicInformationCode) {
            $basicInformationCode .= '<p class="changelog-item"><b>' . $title . ': </b>' . $data . '</p>';
        };

        $type = SystemProcessTypes::gridToString($process->type);
        if($type === null) {
            $type = StandaloneProcesses::toString($process->type);

            $color = StandaloneProcesses::getColor($process->type);
            $bgColor = StandaloneProcesses::getBackgroundColor($process->type);
            $type = '<span style="color: ' . $color . '; background-color: ' . $bgColor . '; padding: 5px; border-radius: 10px">' . $type . '</span>';
        }

        $createRow('Type', $type);

        $statusText = ProcessStatus::toString($process->status);
        $statusFgColor = ProcessStatus::getColor($process->status);
        $statusBgColor = ProcessStatus::getBackgroundColor($process->status);

        $statusCode = '<span style="color: ' . $statusFgColor . '; background-color: ' . $statusBgColor . '; padding: 5px; border-radius: 10px">' . $statusText . '</span>';
        $createRow('Status', $statusCode);

        if($process->documentId !== null) {
            try {
                $document = $this->documentManager->getDocumentById($process->documentId)->title;
            } catch(AException $e) {
                $document = '-';
            }
    
            $createRow('Document', $document);
        }
        
        try {
            $author = $this->app->userManager->getUserById($process->authorUserId)->getFullname();
        } catch(AException $e) {
            $author = '-';
        }

        $createRow('Author', $author);

        if($process->currentOfficerUserId !== null) {
            try {
                $currentOfficer = $this->app->userManager->getUserById($process->currentOfficerUserId)->getFullname();
            } catch(AException $e) {
                $currentOfficer = '-';
            }
        } else {
            $currentOfficer = '-';
        }
        $createRow('Current officer', $currentOfficer);

        if($process->currentOfficerSubstituteUserId !== null) {
            try {
                $currentOfficerSubstitute = $this->app->userManager->getUserById($process->currentOfficerSubstituteUserId)->getFullname();
            } catch(AException $e) {
                $currentOfficerSubstitute = '-';
            }
            $createRow('Current officer\'s substitute', $currentOfficerSubstitute);
        }
        
        $workflowUsers = ProcessHelper::convertWorkflowFromDb($process);

        $i = 1;
        foreach($workflowUsers as $userId) {
            try {
                $workflowUser = $this->app->userManager->getUserById($userId)->getFullname();
            } catch(AException $e) {
                $workflowUser = '-';
            }

            $createRow('Workflow #' . $i, $workflowUser);

            $i++;
        }

        // STANDALONE PROCESSES DATA

        $data = $this->standaloneProcessManager->getProcessData($processId);

        if(!empty($data)) {
            switch($process->type) {
                case StandaloneProcesses::HOME_OFFICE:
                    foreach($data as $key => $value) {
                        $title = HomeOffice::toString($key);

                        if(str_starts_with($key, 'date')) {
                            $value = DateTimeFormatHelper::formatDateToUserFriendly($value, 'd.m.Y');
                        }

                        $createRow($title, $value);
                    }

                    break;
            }
        }

        // END OF STANDALONE PROCESSES DATA

        $this->saveToPresenterCache('process_basic_information', $basicInformationCode);

        // PROCESS ACTIONS
        $actions = [];

        if($process->status == ProcessStatus::IN_PROGRESS) {
            // in progress
            if($process->currentOfficerUserId == $this->getUserId()) {
                // is current officer
                if(ProcessHelper::isUserLastInWorkflow($process->currentOfficerUserId, $workflowUsers)) {
                    // is last
                    $actions['Process'] = 'finish';
                } else {
                    // is not last
                    $actions['Accept'] = 'accept';
                }
    
                $actions['Cancel'] = 'cancel';
            } else {
                // is not current officer
                $actions['Cancel'] = 'cancel';
            }
        }

        $processActionsCode = '';
        if(!empty($actions)) {
            $tmp = [];
            foreach($actions as $title => $action) {
                $params = [
                    'processId' => $processId,
                    'actionName' => $action
                ];

                if($backView !== null) {
                    $params['backView'] = $backView;
                }

                if($process->documentId === null) {
                    $params['isStandalone'] = '1';
                }

                $el = HTML::el('a')
                    ->text($title)
                    ->href($this->createURLString('process', $params))
                    ->title($title)
                    ->class('process-action-link-' . $action);

                $tmp[] = $el->toString();
            }

            $processActionsCode = implode('<br><br>', $tmp);
        } else {
            if($process->status == ProcessStatus::FINISHED) {
                $processActionsCode = 'Process has been finished.';
            } else if($process->status == ProcessStatus::CANCELED) {
                $processActionsCode = 'Process has been canceled.';
            }
        }

        $this->saveToPresenterCache('process_actions', $processActionsCode);

        $links = [];
        if($this->httpRequest->get('disableBackLink') === null) {
            $backLinkParams = [];
            if($backView !== null) {
                $backLinkParams['view'] = $backView;
            }
            
            $links = [
                $this->createBackUrl('list', $backLinkParams)
            ];
        }

        $this->saveToPresenterCache('links', LinkHelper::createLinksFromArray($links));

        $comments = '';
        if(StandaloneProcesses::isCommentingEnabled($process->type)) {
            $commentsTemplate = $this->getTemplate(__DIR__ . '/templates/ProcessesPresenter/profile.comments.html');

            $commentList = [];
            $qb = $this->processRepository->composeQueryForProcessComments($processId);
            $qb->orderBy('dateCreated', 'DESC')
                ->execute();
            while($row = $qb->fetchAssoc()) {
                $row = DatabaseRow::createFromDbRow($row);

                $author = $this->app->userManager->getUserById($row->userId);

                $deleteLink = LinkBuilder::createSimpleLink('Delete', $this->createURL('deleteComment', ['commentId' => $row->commentId, 'processId' => $processId]), 'link');

                if($author->getId() != $this->getUserId()) {
                    $deleteLink = '';
                }

                $commentList[] = '
                    <hr>
                    <div class="row" id="process-comment-' . $row->commentId . '">
                        <div class="col-md">
                            <p style="font-size: 19px">' . $row->description . '</p>
                            <p>Author: ' . $author->getFullname() . ' | Date posted: <span title="' . $row->dateCreated . '">' . DateTimeFormatHelper::formatDateToUserFriendly($row->dateCreated) . '</span></p>
                            ' . $deleteLink . '
                        </div>
                    </div>
                ';
            }

            $commentsTemplate->comments = implode('', $commentList);

            $comments = $commentsTemplate->render()->getRenderedContent();
        }

        $this->saveToPresenterCache('comments', $comments);
    }

    public function renderProfile() {
        $this->template->process_basic_information = $this->loadFromPresenterCache('process_basic_information');
        $this->template->process_actions = $this->loadFromPresenterCache('process_actions');
        $this->template->links = $this->loadFromPresenterCache('links');
        $this->template->process_comments = $this->loadFromPresenterCache('comments');
    }

    public function handleDeleteComment() {
        $processId = $this->httpRequest->get('processId');
        $commentId = $this->httpRequest->get('commentId');

        try {
            $this->processRepository->beginTransaction(__METHOD__);

            $this->processManager->deleteProcessComment($processId, $commentId);

            $this->processRepository->commit($this->getUserId(), __METHOD__);

            $this->flashMessage('Comment deleted.', 'success');
        } catch(AException $e) {
            $this->processRepository->rollback(__METHOD__);

            $this->flashMessage('Could not delete comment. Reason: ' . $e->getMessage(), 'error');
        }

        $this->redirect($this->createURL('profile', ['processId' => $processId]));
    }

    protected function createComponentNewCommentForm(HttpRequest $request) {
        $processId = $request->get('processId');
        try {
            $process = $this->processManager->getProcessById($processId);
        } catch(AException $e) {
            $this->flashMessage('Process not found. Reason: ' . $e->getMessage(), 'error', 10);
            $this->redirect($this->createURL('profile', ['processId' => $processId]));
        }

        $disabled = ($process->currentOfficerUserId == $this->getUserId());

        $form = $this->componentFactory->getFormBuilder();

        $form->setAction($this->createURL('newCommentForm', ['processId' => $processId]));

        $form->addTextArea('text', 'Text:')
            ->setRequired($disabled)
            ->setDisabled(!$disabled)
            ->setPlaceholder('Write a comment text here...');

        $form->addSubmit('Create')
            ->setDisabled(!$disabled);

        return $form;
    }

    public function handleNewCommentForm(?FormRequest $fr = null) {
        if($fr !== null) {
            try {
                $this->processRepository->beginTransaction(__METHOD__);

                $this->processManager->insertNewProcessComment($this->httpRequest->get('processId'), $this->getUserId(), $fr->text);

                $this->processRepository->commit($this->getUserId(), __METHOD__);

                $this->flashMessage('Comment saved.', 'success');
            } catch(AException $e) {
                $this->processRepository->rollback(__METHOD__);

                $this->flashMessage('Could not save comment. Reason: ' . $e->getMessage(), 'error');
            }

            $this->redirect($this->createURL('profile', ['processId' => $this->httpRequest->get('processId')]));
        }
    }

    public function handleProcess() {
        $processId = $this->httpRequest->get('processId');
        if($processId === null) {
            throw new RequiredAttributeIsNotSetException('processId');
        }
        $action = $this->httpRequest->get('actionName');
        if($action === null) {
            throw new RequiredAttributeIsNotSetException('action');
        }
        $backView = $this->httpRequest->get('backView');
        $isStandalone = $this->httpRequest->get('isStandalone');

        try {
            $process = $this->processManager->getProcessById($processId);
        } catch(AException $e) {
            $this->flashMessage('Process not found. Reason: ' . $e->getMessage(), 'error', 10);

            $params = [];
            if($backView !== null) {
                $params['view'] = $backView;
            }

            $this->redirect($this->createURL('list', $params));
        }

        try {
            switch($action) {
                case 'cancel':
                    $this->processManager->cancelProcess($processId, 'Canceled by user.', $this->getUserId());
    
                    $this->flashMessage('Process canceled.', 'success');
                    break;
                    
                case 'accept':
                    $this->processManager->nextWorkflowProcess($processId, $this->getUserId());
    
                    $this->flashMessage('Process moved to next user in the workflow.', 'success');
                    break;
    
                case 'finish':
                    if($isStandalone === null) {
                        $this->processFactory->startDocumentProcessFinalExecute($process->type, $process->documentId);
                    }

                    $this->processManager->finishProcess($processId, $this->getUserId());

                    if($isStandalone !== null) {
                        switch($process->type) {
                            case StandaloneProcesses::INVOICE:
                                $this->standaloneProcessManager->finishInvoice($processId);
                                break;
                        }
                    }
    
                    $this->flashMessage('Process finished.', 'success');
                    break;
            }
        } catch(AException $e) {
            $this->flashMessage('Could not process the process. Reason: ' . $e->getMessage(), 'error', 10);
        }

        $params = [
            'processId' => $processId
        ];
        if($backView !== null) {
            $params['backView'] = $backView;
        }

        $this->redirect($this->createURL('profile', $params));
    }
}

?>