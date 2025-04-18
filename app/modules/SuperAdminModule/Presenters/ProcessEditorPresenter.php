<?php

namespace App\Modules\SuperAdminModule;

use App\Core\AjaxRequestBuilder;
use App\Core\Http\FormRequest;
use App\Core\Http\HttpRequest;
use App\Core\Http\JsonResponse;
use App\Exceptions\AException;
use App\UI\FormBuilder2\JSON2FB;

class ProcessEditorPresenter extends ASuperAdminPresenter {
    public function __construct() {
        parent::__construct('ProcessEditorPresenter', 'Process editor');
    }

    public function renderForm() {
        $this->template->links = $this->createBackFullUrl('SuperAdmin:Processes', 'list');
    }

    protected function createComponentProcessForm(HttpRequest $request) {
        $process = null;
        if($request->get('processId') !== null && $request->get('uniqueProcessId') !== null) {
            $processId = $this->httpRequest->get('processId');

            $process = $this->app->processManager->getProcessById($processId);
        }

        $form = $this->componentFactory->getFormBuilder();

        if($process !== null) {
            $params = [
                'processId' => $request->get('processId'),
                'uniqueProcessId' => $request->get('uniqueProcessId')
            ];

            $form->setAction($this->createURL('formSubmit', $params));
        } else {
            $form->setAction($this->createURL('formSubmit'));
        }

        $title = $form->addTextInput('title', 'Title:')
            ->setRequired();

        if($process !== null) {
            $title->setValue($process->title);
        }

        $description = $form->addTextArea('description', 'Description:')
            ->setRequired();

        if($process !== null) {
            $description->setContent($process->description);
        }

        $form->addSubmit('Go to editor');

        return $form;
    }
    
    public function handleFormSubmit(FormRequest $fr) {
        $title = $fr->title;
        $description = $fr->description;

        $json = json_encode(['title' => $title, 'description' => $description]);

        $params = [];

        if($this->httpRequest->get('processId') !== null && $this->httpRequest->get('uniqueProcessId') !== null) {
            $params['processId'] = $this->httpRequest->get('processId');
            $params['uniqueProcessId'] = $this->httpRequest->get('uniqueProcessId');
        }

        $params['formdata'] = base64_encode($json);

        $this->redirect($this->createURL('editor', $params));
    }

    public function handleEditor(?FormRequest $fr = null) {
        if($fr !== null) {
            try {
                $formdata = $this->httpRequest->get('formdata');
                $formdata = json_decode(base64_decode($formdata), true);

                $title = $formdata['title'];
                $description = $formdata['description'];

                $code = base64_encode($fr->formDefinition);

                $this->app->processRepository->beginTransaction(__METHOD__);

                $this->app->processManager->createNewProcess($title, $description, $this->getUserId(), $code);

                $this->app->processRepository->commit($this->getUserId(), __METHOD__);

                $this->flashMessage('Successfully created a new process.', 'success');
            } catch(AException $e) {
                $this->app->processRepository->rollback(__METHOD__);

                $this->flashMessage('Could not create a new process. Reason: ' . $e->getMessage(), 'error', 10);
            }

            $this->redirect($this->createFullURL('SuperAdmin:Processes', 'list'));
        }
    }

    public function renderEditor() {
        $this->template->links = $this->createBackFullUrl('SuperAdmin:Processes', 'list');
    }

    protected function createComponentProcessEditor(HttpRequest $request) {
        $process = null;
        if($request->get('processId') !== null && $request->get('uniqueProcessId') !== null) {
            $processId = $this->httpRequest->get('processId');

            $process = $this->app->processManager->getProcessById($processId);
        }

        $form = $this->componentFactory->getFormBuilder();

        $form->setAction($this->createURL('editor', ['formdata' => $request->get('formdata')]));

        $form->addLabel('formDefinition_label', 'Form JSON definition:')
            ->setRequired();

        $formDefinition = $form->addTextArea('formDefinition')
            ->setRequired()
            ->setLines(20);

        if($process !== null) {
            $dbForm = base64_decode($process->form);
            $formDefinition->setContent($dbForm);
        }

        $form->addButton('View')
            ->setOnClick('sendLiveview()');

        $form->addSubmit('Save');

        $arb = new AjaxRequestBuilder();

        $arb->setAction($this, 'editorLiveview')
            ->setMethod('POST')
            ->setHeader(['code' => '_code'])
            ->setFunctionName('editorLiveview')
            ->setFunctionArguments(['_code'])
            ->updateHTMLElement('form-live-view', 'form');

        $form->addScript($arb);

        $code = '
            function sendLiveview() {
                const _code = $("#formDefinition").val();

                if(_code == "") {
                    alert("No code entered.");
                    return;
                }

                var _json = "";

                try {
                    _json = JSON.parse(_code);

                    editorLiveview(JSON.stringify(_json));
                } catch(exception) {
                    alert("Could not parse JSON. Reason: " + exception);
                }
            }
        ';

        $form->addScript($code);

        return $form;
    }

    public function actionEditorLiveview() {
        $jsonCode = $this->httpRequest->get('code');

        $decodedJson = json_decode($jsonCode, true);

        if($decodedJson === null) {
            return new JsonResponse(['error' => '1', 'errorMsg' => 'The form JSON entered is incorrect.']);
        }

        $form = $this->componentFactory->getFormBuilder();

        $helper = new JSON2FB($form, $decodedJson);

        $helper->setSkipAttributes(['action']);
        $helper->addSkipElementAttributes('userSelect', 'containerId');

        try {
            $code = $helper->render();

            return new JsonResponse(['form' => $code]);
        } catch(AException $e) {
            return new JsonResponse(['error' => '1', 'errorMsg' => $e->getMessage()]);
        }
    }
}

?>