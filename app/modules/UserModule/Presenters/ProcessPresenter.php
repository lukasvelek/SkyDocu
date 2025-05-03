<?php

namespace App\Modules\UserModule;

use App\Constants\Container\ProcessInstanceOperations;
use App\Constants\Container\ProcessInstanceStatus;
use App\Core\Http\HttpRequest;
use App\Exceptions\AException;
use App\Helpers\LinkHelper;
use App\UI\FormBuilder2\Button;
use App\UI\FormBuilder2\JSON2FB;
use App\UI\LinkBuilder;

class ProcessPresenter extends AUserPresenter {
    public function __construct() {
        parent::__construct('ProcessPresenter', 'Process');
    }

    public function renderProcessForm() {
        $this->template->links = $this->createBackFullUrl('User:Processes', 'list', ['view' => 'waitingForMe']);

        $process = $this->processManager->getProcessById($this->httpRequest->get('processId'));
        
        $this->template->process_title = $process->title;
        
        // PROCESS FORM
        $instance = $this->processInstanceManager->getProcessInstanceById($this->httpRequest->get('instanceId'));

        $form = $this->componentFactory->getFormBuilder();
        
        $json = json_decode(base64_decode($process->form), true);

        $data = unserialize($instance->data);

        $json2fb = new JSON2FB($form, $json, $this->containerId);
        $json2fb->setSkipAttributes(['action']);
        $json2fb->setFormData($data);
        $json2fb->callAfterSubmitReducer();

        $this->template->process_form = $json2fb->render();

        // PROCESS CONTROLS
        $workflow = unserialize($process->workflow);
        $workflowConfiguration = unserialize($process->workflowConfiguration);
        $workflowIndex = $data['workflowIndex'];

        $currentWorkflow = $workflow[$workflowIndex]; // e.g. $ADMINISTRATORS$

        $countInWorkflow = 0;
        foreach($workflow as $w) {
            if($w == $currentWorkflow) {
                $countInWorkflow++;
            }
        }

        $configuration = null;
        if($countInWorkflow > 1) {
            $configuration = $workflowConfiguration[$currentWorkflow . '_' . $workflowIndex];
        } else {
            $configuration = $workflowConfiguration[$currentWorkflow];
        }

        if($configuration === null) {
            $this->template->process_controls = '';
        }

        $getLink = function(string $operation) {
            return $this->createURLString('processOperation', ['operation' => $operation, 'processId' => $this->httpRequest->get('processId'), 'instanceId' => $this->httpRequest->get('instanceId')]);
        };

        $links = [];
        foreach($configuration as $operation) {
            $url = $getLink($operation);
            
            $btn = new Button('button', ucfirst($operation));
            $btn->setOnClick("location.href='" . $url . "'");

            $links[] = $btn->render();
        }

        $this->template->process_controls = implode('', $links);
    }

    public function handleProcessOperation() {
        $processId = $this->httpRequest->get('processId');
        $instanceId = $this->httpRequest->get('instanceId');
        $operation = $this->httpRequest->get('operation');

        try {
            $fm = 'Operation successfully processed.';

            switch($operation) {
                case ProcessInstanceOperations::ACCEPT:
                    // move to next step
                    // 1. accept
                    $this->processInstanceManager->acceptProcessInstance($instanceId, $this->getUserId());
    
                    // 2. change workflow
                    $process = $this->processManager->getProcessById($processId);
                    $workflow = unserialize($process->workflow);
    
                    $instance = $this->processInstanceManager->getProcessInstanceById($instanceId);
                    $data = unserialize($instance->data);
                    $index = $data['workflowIndex'] + 1;
    
                    [$officer, $officerType] = $this->processInstanceManager->evaluateNextProcessInstanceOfficer($workflow, $this->getUserId(), $index);
    
                    if($officer === null && $officerType === null) {
                        // user is last -> finish
                        $this->processInstanceManager->changeProcessInstanceStatus($instanceId, ProcessInstanceStatus::FINISHED);
                        $fm = 'Process successfully finished.';
                    } else {
                        $this->processInstanceManager->moveProcessInstanceToNextOfficer($instanceId, $officer, $officerType);
                        $fm = 'Process successfully moved to next officer.';
                    }

                    break;
            
                case ProcessInstanceOperations::ARCHIVE:
                    // finish and archive the process
                    $this->processInstanceManager->archiveProcessInstance($instanceId, $this->getUserId());
                    $fm = 'Process succesfully archived.';
                    break;
    
                case ProcessInstanceOperations::CANCEL:
                    // cancel the process in current step
                    $this->processInstanceManager->cancelProcessInstance($instanceId, $this->getUserId(), 'User canceled the process.');
                    $fm = 'Process successfully canceled.';
                    break;
    
                case ProcessInstanceOperations::FINISH:
                    // finish the process
                    $this->processInstanceManager->changeProcessInstanceStatus($instanceId, ProcessInstanceStatus::FINISHED);
                    $fm = 'Process successfully finished.';
                    break;
    
                case ProcessInstanceOperations::REJECT:
                    // reject and finish the process
                    $this->processInstanceManager->rejectProcessInstance($instanceId, $this->getUserId());
                    $fm = 'Process successfully rejected.';
                    break;
            }

            $this->flashMessage($fm, 'success');
        } catch(AException $e) {
            $this->flashMessage('Could not process operation. Reason: ' . $e->getMessage(), 'error', 10);
        }

        $this->redirect($this->createFullURL('User:Processes', 'list', ['view' => 'waitingForMe']));
    }
}

?>