<?php

namespace App\Modules\UserModule;

use App\Constants\Container\ProcessInstanceOperations;
use App\Constants\Container\ProcessInstanceStatus;
use App\Core\Http\FormRequest;
use App\Entities\ProcessInstanceDataEntity;
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

        $instanceData = ProcessInstanceDataEntity::createFromSerializedData($instance->data);

        $definition = json_decode(base64_decode($process->definition), true);
        $forms = $definition['forms'];

        $workflow = [];
        foreach($forms as $_form) {
            $workflow[] = $_form['actor'];
        }

        $renderedForms = [];
        $addToDisplay = function(string $code) use (&$renderedForms) {
            $renderedForms[] = $code;
        };
        
        for($i = 0; $i <= $instanceData->getWorkflowIndex(); $i++) {
            // cascade forms

            $json = json_decode($forms[$i]['form'], true);

            if(array_key_exists('operations', $json)) continue;

            $_form = $instanceData->getFormByIndex($i);

            $formUserId = null;
            $formCode = '<div>';
            if($_form !== null) {
                $formUserId = $_form['userId'];

                $formUser = $this->app->userManager->getUserById($formUserId);

                $formCode .= '<b>User: ' . $formUser->getFullname() . '</b><br><br>';
            }

            $formData = $instanceData->getFormByIndex($i)['data'] ?? [];

            $form = $this->componentFactory->getFormBuilder();

            $json2fb = new JSON2FB($form, $json, $this->containerId);
            $json2fb->setSkipAttributes(['action']);
            $json2fb->setFormData($formData);
            $json2fb->callAfterSubmitReducer();
            if($i < $instanceData->getWorkflowIndex() || in_array($instance->status, [
                ProcessInstanceStatus::ARCHIVED,
                ProcessInstanceStatus::CANCELED,
                ProcessInstanceStatus::FINISHED
            ])) {
                $json2fb->removeButtons();
            }
            $json2fb->setCustomUrlParams([
                'processId' => $this->httpRequest->get('processId'),
                'instanceId' => $this->httpRequest->get('instanceId'),
                'view' => $view
            ]);
            $json2fb->setFormHandleButtonsParams($this->createURL('processOperation', [
                'processId' => $this->httpRequest->get('processId'),
                'instanceId' => $this->httpRequest->get('instanceId'),
                'view' => $view
            ]));

            if($i == $instanceData->getWorkflowIndex()) {
                $form = $json2fb->getFormBuilder();
                $form->setAction($this->createURL('processOperation', [
                    'processId' => $this->httpRequest->get('processId'),
                    'instanceId' => $this->httpRequest->get('instanceId'),
                    'view' => $view,
                    'operation' => 'submit'
                ]));

                $formCode .= $form->render() . '</div>';
            } else {
                $formCode .= $json2fb->render() . '</div>';
            }

            $addToDisplay($formCode);
        }

        $this->template->process_form = implode('<hr>', $renderedForms);
    }

    public function handleProcessOperation(?FormRequest $fr = null) {
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

            $description = '';
            
            $instance = $this->processInstanceManager->getProcessInstanceById($instanceId);
            
            $instanceData = ProcessInstanceDataEntity::createFromSerializedData($instance->data);
            
            try {
                $this->processInstanceRepository->beginTransaction(__METHOD__);

                switch($operation) {
                    case 'submit':
                        $definition = json_decode(base64_decode($process->definition), true);

                        $forms = $definition['forms'];

                        $workflow = [];
                        $i = 0;
                        foreach($forms as $form) {
                                $workflow[] = $form['actor'];
                            $i++;
                        }

                        [$officer, $officerType] = $this->processInstanceManager->evaluateNextProcessInstanceOfficer($workflow, $this->getUserId(), $instanceData->getWorkflowIndex() + 1);

                        if($officer === null && $officerType === null) {
                            // user is last -> accept
                            $this->processInstanceManager->finishProcessInstance($instanceId, $this->getUserId());
                            $description = sprintf('Finished %s', $process->title);
                            $fm = 'Process successfully finished.';
                        } else {
                            // move to next user
                            $this->processInstanceManager->moveProcessInstanceToNextOfficer($instanceId, $this->getUserId(), $officer, $officerType);
                            $description = sprintf('%s waiting for your reaction', $process->title);
                            $fm = 'Process successfully moved to next officer.';
                        }

                        break;

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
                                $workflow[] = $form['actor'];
                            $i++;
                        }

                        [$officer, $officerType] = $this->processInstanceManager->evaluateNextProcessInstanceOfficer($workflow, $this->getUserId(), $instanceData->getWorkflowIndex() + 1);
        
                        if($officer === null && $officerType === null) {
                            // user is last -> finish
                            $this->processInstanceManager->finishProcessInstance($instanceId, $this->getUserId());
                            $description = sprintf('Finished %s', $process->title);
                            $fm = 'Process successfully finished.';
                        } else {
                            $this->processInstanceManager->moveProcessInstanceToNextOfficer($instanceId, $this->getUserId(), $officer, $officerType);
                            $description = sprintf('%s waiting for your reaction', $process->title);
                            $fm = 'Process successfully moved to next officer.';
                        }
                        break;
                
                    case ProcessInstanceOperations::ARCHIVE:
                        // finish and archive the process
                        $this->processInstanceManager->archiveProcessInstance($instanceId, $this->getUserId());
                        $description = sprintf('Archived %s', $process->title);
                        $fm = 'Process succesfully archived.';
                        break;
        
                    case ProcessInstanceOperations::CANCEL:
                        // cancel the process in current step
                        $this->processInstanceManager->cancelProcessInstance($instanceId, $this->getUserId());
                        $description = sprintf('Canceled %s', $process->title);
                        $fm = 'Process successfully canceled.';
                        break;
        
                    case ProcessInstanceOperations::FINISH:
                        // finish the process
                        $this->processInstanceManager->changeProcessInstanceStatus($instanceId, ProcessInstanceStatus::FINISHED);
                        $description = sprintf('Finished %s', $process->title);
                        $fm = 'Process successfully finished.';
                        break;
        
                    case ProcessInstanceOperations::REJECT:
                        // reject and finish the process
                        $this->processInstanceManager->rejectProcessInstance($instanceId, $this->getUserId());
                        $description = sprintf('Rejected %s', $process->title);
                        $fm = 'Process successfully rejected.';
                        break;
                }

                $definition = json_decode(base64_decode($process->definition), true);
                $forms = $definition['forms'];
                $_form = json_decode($forms[$instanceData->getWorkflowIndex()]['form'], true);

                // global workflow step description
                if(array_key_exists('instanceDescription', $_form)) {
                    $description = $_form['instanceDescription'];
                }
                
                $operationName = $operation . 'Button';
                foreach(json_decode($_form['form'], true)['elements'] as $element) {
                    if($operationName == $element['type'] && array_key_exists('instanceDescription', $element)) {
                        $description = $element['instanceDescription'];
                        break;
                    }
                }

                $this->processInstanceManager->changeProcessInstanceDescription($instanceId, $description);

                $this->processInstanceRepository->commit($this->getUserId(), __METHOD__);
            } catch(AException $e) {
                $this->processInstanceRepository->rollback(__METHOD__);
                
                throw $e;
            }

            // in order to have new data
            $instance = $this->processInstanceManager->getProcessInstanceById($instanceId);
            $instanceData = ProcessInstanceDataEntity::createFromSerializedData($instance->data);

            try {
                $this->processInstanceRepository->beginTransaction(__METHOD__);

                // save data
                if($fr !== null) {
                    $instanceData->addFormData($this->getUserId(), $fr->getData());
                    $this->processInstanceManager->updateInstance($instanceId, [
                        'data' => $instanceData->serialize()
                    ]);
                }

                $this->processInstanceRepository->commit($this->getUserId(), __METHOD__);
            } catch(AException $e) {
                $this->processInstanceRepository->rollback(__METHOD__);

                throw $e;
            }

            $this->flashMessage($fm, 'success');
        } catch(AException $e) {

            $this->flashMessage('Could not process operation. Reason: ' . $e->getMessage(), 'error', 10);
        }

        $this->redirect($this->createFullURL('User:Processes', 'list', ['view' => $view]));
    }
}

?>