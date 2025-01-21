<?php

namespace App\Modules\UserModule;

use App\Components\ProcessesGrid\ProcessesGrid;
use App\Components\ProcessViewSidebar\ProcessViewSidebar;
use App\Constants\Container\ProcessFormValues\HomeOffice;
use App\Constants\Container\ProcessGridViews;
use App\Constants\Container\ProcessStatus;
use App\Constants\Container\StandaloneProcesses;
use App\Constants\Container\SystemProcessTypes;
use App\Core\Http\HttpRequest;
use App\Exceptions\AException;
use App\Helpers\DateTimeFormatHelper;
use App\Helpers\ProcessHelper;
use App\UI\LinkBuilder;

class ProcessesPresenter extends AUserPresenter {
    public function __construct() {
        parent::__construct('ProcessesPresenter', 'Processes');
    }

    public function handleList() {
        $view = $this->httpGet('view');

        if($view === null) {
            $this->redirect($this->createURL('list', ['view' => ProcessGridViews::VIEW_ALL]));
        }
    }

    public function renderList() {
        $this->template->links = [];
    }

    protected function createComponentProcessViewsSidebar(HttpRequest $request) {
        $sidebar = new ProcessViewSidebar($request);

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

        $grid->setView($request->query['view']);
    
        return $grid;
    }

    public function handleProfile() {
        $processId = $this->httpGet('processId', true);
        $backView = $this->httpGet('backView');

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

                $tmp[] = LinkBuilder::createSimpleLink($title, $this->createURL('process', $params), 'link');
            }

            $processActionsCode = implode('<br>', $tmp);
        } else {
            if($process->status == ProcessStatus::FINISHED) {
                $processActionsCode = 'Process has been finished.';
            } else if($process->status == ProcessStatus::CANCELED) {
                $processActionsCode = 'Process has been canceled.';
            }
        }

        $this->saveToPresenterCache('process_actions', $processActionsCode);

        $links = [];
        if($this->httpGet('disableBackLink') === null) {
            $backLinkParams = [];
            if($backView !== null) {
                $backLinkParams['view'] = $backView;
            }
            
            $links = [
                $this->createBackUrl('list', $backLinkParams)
            ];
        }

        $this->saveToPresenterCache('links', implode('&nbsp;&nbsp;', $links));
    }

    public function renderProfile() {
        $this->template->process_basic_information = $this->loadFromPresenterCache('process_basic_information');
        $this->template->process_actions = $this->loadFromPresenterCache('process_actions');
        $this->template->links = $this->loadFromPresenterCache('links');
    }

    public function handleProcess() {
        $processId = $this->httpGet('processId', true);
        $action = $this->httpGet('actionName', true);
        $backView = $this->httpGet('backView');
        $isStandalone = $this->httpGet('isStandalone');

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