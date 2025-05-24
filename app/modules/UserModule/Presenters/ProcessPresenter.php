<?php

namespace App\Modules\UserModule;

use App\Constants\Container\ProcessInstanceOperations;
use App\Constants\Container\ProcessInstanceStatus;
use App\Exceptions\AException;
use App\Exceptions\GeneralException;
use App\UI\FormBuilder2\JSON2FB;

class ProcessPresenter extends AUserPresenter {
    public function __construct() {
        parent::__construct('ProcessPresenter', 'Process');
    }

    public function renderProcessForm() {
        $view = $this->httpRequest->get('view');

        $this->template->links = $this->createBackFullUrl('User:Processes', 'list', ['view' => $view]);

        $process = $this->processManager->getProcessById($this->httpRequest->get('processId'));
        
        $this->template->process_title = $process->title;
        
        // PROCESS FORM
        $instance = $this->processInstanceManager->getProcessInstanceById($this->httpRequest->get('instanceId'));

        $data = unserialize($instance->data);

        $form = $this->componentFactory->getFormBuilder();

        $definition = json_decode(base64_decode($process->definition), true);
        $forms = $definition['forms'];

        $workflow = [];
        foreach($forms as $_form) {
            $workflow[] = $_form['actor'];
        }

        $json = json_decode($forms[$data['workflowIndex']]['form'], true);

        $json2fb = new JSON2FB($form, $json, $this->containerId);
        $json2fb->setSkipAttributes(['action']);
        $json2fb->setFormData($data);
        $json2fb->callAfterSubmitReducer();
        $json2fb->removeButtons();

        $renderedForms = [
            $json2fb->render()
        ];

        $form = $this->componentFactory->getFormBuilder();

        $json = json_decode($forms[$data['workflowIndex'] + 1]['form'], true);

        $json2fb = new JSON2FB($form, $json, $this->containerId);
        $json2fb->setSkipAttributes(['action']);
        $json2fb->setFormData($data);
        $json2fb->callAfterSubmitReducer();
        $json2fb->setFormHandleButtonsParams($this->createURL('processOperation', ['processId' => $this->httpRequest->get('processId'), 'instanceId' => $this->httpRequest->get('instanceId'), 'view' => $view]));

        $renderedForms[] = $json2fb->render();

        $this->template->process_form = implode('<hr>', $renderedForms);
    }

    public function handleProcessOperation() {
        $processId = $this->httpRequest->get('processId');
        $instanceId = $this->httpRequest->get('instanceId');
        $operation = $this->httpRequest->get('operation');
        $view = $this->httpRequest->get('view');

        $process = $this->processManager->getProcessById($processId);

        try {
            if(!$this->containerProcessAuthorizator->canUserProcessInstance($instanceId, $this->getUserId())) {
                throw new GeneralException('You are not allowed to perform any actions in this process.');
            }

            $fm = 'Operation successfully processed.';

            $this->processInstanceRepository->beginTransaction(__METHOD__);

            switch($operation) {
                case ProcessInstanceOperations::ACCEPT:
                    // move to next step
                    // 1. accept
                    $this->processInstanceManager->acceptProcessInstance($instanceId, $this->getUserId());
    
                    // 2. change workflow
                    $definition = json_decode(base64_decode($process->definition), true);

                    $forms = $definition['forms'];

                    $workflow = [];
                    $i = 0;
                    foreach($forms as $form) {
                        if($i > 0) {
                            $workflow[] = $form['actor'];
                        }
                        $i++;
                    }
    
                    $instance = $this->processInstanceManager->getProcessInstanceById($instanceId);
                    $data = unserialize($instance->data);
                    $index = $data['workflowIndex'] + 1;

                    [$officer, $officerType] = $this->processInstanceManager->evaluateNextProcessInstanceOfficer($workflow, $this->getUserId(), $index);
    
                    if($officer === null && $officerType === null) {
                        // user is last -> finish
                        $this->processInstanceManager->changeProcessInstanceStatus($instanceId, ProcessInstanceStatus::FINISHED);
                        $this->processInstanceManager->changeProcessInstanceDescription($instanceId, sprintf('Finished %s', $process->title));
                        $fm = 'Process successfully finished.';
                    } else {
                        $this->processInstanceManager->moveProcessInstanceToNextOfficer($instanceId, $officer, $officerType);
                        $this->processInstanceManager->changeProcessInstanceDescription($instanceId, sprintf('%s waiting for your reaction.', $process->title));
                        $fm = 'Process successfully moved to next officer.';
                    }

                    break;
            
                case ProcessInstanceOperations::ARCHIVE:
                    // finish and archive the process
                    $this->processInstanceManager->archiveProcessInstance($instanceId, $this->getUserId());
                    $this->processInstanceManager->changeProcessInstanceDescription($instanceId, sprintf('Archived %s', $process->title));
                    $fm = 'Process succesfully archived.';
                    break;
    
                case ProcessInstanceOperations::CANCEL:
                    // cancel the process in current step
                    $this->processInstanceManager->cancelProcessInstance($instanceId, $this->getUserId());
                    $this->processInstanceManager->changeProcessInstanceDescription($instanceId, sprintf('Canceled %s', $process->title));
                    $fm = 'Process successfully canceled.';
                    break;
    
                case ProcessInstanceOperations::FINISH:
                    // finish the process
                    $this->processInstanceManager->changeProcessInstanceStatus($instanceId, ProcessInstanceStatus::FINISHED);
                    $this->processInstanceManager->changeProcessInstanceDescription($instanceId, sprintf('Finished %s', $process->title));
                    $fm = 'Process successfully finished.';
                    break;
    
                case ProcessInstanceOperations::REJECT:
                    // reject and finish the process
                    $this->processInstanceManager->rejectProcessInstance($instanceId, $this->getUserId());
                    $this->processInstanceManager->changeProcessInstanceDescription($instanceId, sprintf('Rejected %s', $process->title));
                    $fm = 'Process successfully rejected.';
                    break;
            }

            $this->processInstanceRepository->commit($this->getUserId(), __METHOD__);

            $this->flashMessage($fm, 'success');
        } catch(AException $e) {
            $this->processInstanceRepository->rollback(__METHOD__);

            $this->flashMessage('Could not process operation. Reason: ' . $e->getMessage(), 'error', 10);
        }

        $this->redirect($this->createFullURL('User:Processes', 'list', ['view' => $view]));
    }
}

?>