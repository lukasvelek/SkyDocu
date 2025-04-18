<?php

namespace App\Modules\SuperAdminModule;

use App\Core\AjaxRequestBuilder;
use App\Core\Http\FormRequest;
use App\Core\Http\HttpRequest;
use App\Core\Http\JsonResponse;
use App\Exceptions\AException;
use App\UI\FormBuilder2\JSON2FB;

class NewProcessEditorPresenter extends ASuperAdminPresenter {
    public function __construct() {
        parent::__construct('NewProcessEditorPresenter', 'New process editor');
    }

    public function renderForm() {
        $this->template->links = $this->createBackFullUrl('SuperAdmin:Processes', 'list');
    }

    protected function createComponentProcessForm(HttpRequest $request) {
        $form = $this->componentFactory->getFormBuilder();

        $form->setAction($this->createURL('formSubmit'));

        $form->addTextInput('title', 'Title:')
            ->setRequired();

        $form->addTextArea('description', 'Description:')
            ->setRequired();

        $form->addSubmit('Go to editor');

        return $form;
    }
    
    public function handleFormSubmit(FormRequest $fr) {
        $title = $fr->title;
        $description = $fr->description;

        $json = json_encode(['title' => $title, 'description' => $description]);

        $this->redirect($this->createURL('editor', ['formdata' => base64_encode($json)]));
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
        $form = $this->componentFactory->getFormBuilder();

        $form->setAction($this->createURL('editor', ['formdata' => $request->get('formdata')]));

        $form->addLabel('formDefinition_label', 'Form JSON definition:')
            ->setRequired();

        $form->addTextArea('formDefinition')
            ->setRequired()
            ->setLines(20);

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