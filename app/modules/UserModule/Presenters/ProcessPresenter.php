<?php

namespace App\Modules\UserModule;

use App\Core\Http\HttpRequest;
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
            return $this->createURLString('processOperation', ['operation' => $operation]);
        };

        $links = [];
        foreach($configuration as $operation) {
            $url = $getLink($operation);
            
            $btn = new Button('button', ucfirst($operation));
            $btn->setOnClick("location.href='" . $url . "';");

            $links[] = $btn->render();
        }

        $this->template->process_controls = implode('', $links);
    }

    public function handleProcessOperation() {

    }
}

?>