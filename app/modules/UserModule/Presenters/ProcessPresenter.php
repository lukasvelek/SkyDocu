<?php

namespace App\Modules\UserModule;

use App\Core\Http\HttpRequest;
use App\UI\FormBuilder2\JSON2FB;

class ProcessPresenter extends AUserPresenter {
    public function __construct() {
        parent::__construct('ProcessPresenter', 'Process');
    }

    public function renderProcessForm() {
        $this->template->links = $this->createBackFullUrl('User:Processes', 'list', ['view' => 'waitingForMe']);

        $process = $this->processManager->getProcessById($this->httpRequest->get('processId'));
        
        $this->template->process_title = $process->title;
        
        $instance = $this->processInstanceManager->getProcessInstanceById($this->httpRequest->get('instanceId'));

        $form = $this->componentFactory->getFormBuilder();
        
        $json = json_decode(base64_decode($process->form), true);

        $data = unserialize($instance->data);

        $json2fb = new JSON2FB($form, $json, $this->containerId);
        $json2fb->setSkipAttributes(['action']);
        $json2fb->setFormData($data);
        $json2fb->callAfterSubmitReducer();

        $this->template->process_form = $json2fb->render();
    }

    protected function createComponentProcessForm(HttpRequest $request) {
        $process = $this->processManager->getProcessById($request->get('processId'));
        $instance = $this->processInstanceManager->getProcessInstanceById($request->get('instanceId'));

        $form = $this->componentFactory->getFormBuilder();

        $json = json_decode(base64_decode($process->form), true);

        $json2fb = new JSON2FB($form, $json, $this->containerId);
        $json2fb->setSkipAttributes(['action']);
        
        $form = $json2fb->getFormBuilder();
        
        $data = unserialize($instance->data);

        $stateList = $form->getStateList();

        foreach($data as $key => $value) {
            if($stateList->$key !== null) {
                $stateList->$key->value = $value;
            }
        }

        foreach($stateList->getAll() as $name => $state) {
            $state->isReadonly = true;
        }

        $form->applyStateList($stateList);
        $form->setOverrideReducerCallOnStartup();

        return $form;
    }
}

?>