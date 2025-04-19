<?php

namespace App\Modules\SuperAdminModule;

use App\Constants\Container\ProcessInstanceOperations;
use App\Constants\Container\ProcessStatus as ContainerProcessStatus;
use App\Constants\ProcessStatus;
use App\Core\AjaxRequestBuilder;
use App\Core\Http\FormRequest;
use App\Core\Http\HttpRequest;
use App\Core\Http\JsonResponse;
use App\Exceptions\AException;
use App\Exceptions\GeneralException;
use App\Repositories\Container\ProcessRepository;
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
                $oldProcessId = null;
                if($this->httpRequest->get('processId') !== null) {
                    $oldProcessId = $this->httpRequest->get('processId');
                }

                $formdata = $this->httpRequest->get('formdata');
                $formdata = json_decode(base64_decode($formdata), true);

                $title = $formdata['title'];
                $description = $formdata['description'];

                $code = base64_encode($fr->formDefinition);

                if($oldProcessId !== null) {
                    $oldProcess = $this->app->processManager->getProcessById($oldProcessId);

                    if($oldProcess->title == $title &&
                        $oldProcess->description == $description &&
                        json_decode(base64_decode($oldProcess->form), true) == json_decode(base64_decode($code), true)) {
                        // is the same - lets go straight to the workflow editor

                        $params = [
                            'processId' => $oldProcessId,
                            'uniqueProcessId' => $oldProcess->uniqueProcessId
                        ];

                        $this->redirect($this->createURL('workflowEditor', $params));
                    }
                }

                $this->app->processRepository->beginTransaction(__METHOD__);

                // add new version
                [$processId, $uniqueProcessId] = $this->app->processManager->createNewProcess($title, $description, $this->getUserId(), $code, $oldProcessId, ProcessStatus::NEW);

                // remove old version from distribution
                /*if($oldProcessId !== null) {
                    $this->app->processManager->updateProcess($oldProcessId, ['status' => ProcessStatus::NOT_IN_DISTRIBUTION]);
                }*/

                /*$containers = $this->app->containerManager->getAllContainers(true, true);

                foreach($containers as $container) {
                    /**
                     * @var \App\Entities\ContainerEntity $container
                     */

                    /*if(!$container->isInDistribution()) continue;

                    $dbConn = $this->app->dbManager->getConnectionToDatabase($container->getDefaultDatabase()->getName());

                    $processRepository = new ProcessRepository($dbConn, $this->logger, $this->app->userRepository->transactionLogRepository);

                    $processRepository->removeCurrentDistributionProcessFromDistributionForUniqueProcessId($uniqueProcessId);

                    $processRepository->addNewProcess($processId, $uniqueProcessId, $title, $description, $code, $this->getUserId(), ContainerProcessStatus::NEW);
                }*/

                $this->app->processRepository->commit($this->getUserId(), __METHOD__);

                $this->flashMessage('Successfully created a new process. Now, you have to define workflow.', 'success');

                $params = [
                    'processId' => $processId,
                    'uniqueProcessId' => $uniqueProcessId
                ];
    
                if($oldProcessId !== null) {
                    $params['oldProcessId'] = $oldProcessId;
                }

                $this->redirect($this->createURL('workflowEditor', $params));
            } catch(AException $e) {
                $this->app->processRepository->rollback(__METHOD__);

                $this->flashMessage('Could not create a new process. Reason: ' . $e->getMessage(), 'error', 10);

                $this->redirect($this->createFullURL('SuperAdmin:Processes', 'list'));
            }
        }
    }

    public function renderEditor() {
        $this->template->links = $this->createBackFullUrl('SuperAdmin:Processes', 'list');
    }

    protected function createComponentProcessEditor(HttpRequest $request) {
        $params = [];

        $process = null;
        if($request->get('processId') !== null && $request->get('uniqueProcessId') !== null) {
            $processId = $this->httpRequest->get('processId');

            $process = $this->app->processManager->getProcessById($processId);

            $params['processId'] = $processId;
            $params['uniqueProcessId'] = $process->uniqueProcessId;
        }

        $params['formdata'] = $request->get('formdata');

        $form = $this->componentFactory->getFormBuilder();

        $form->setAction($this->createURL('editor', $params));

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

    public function renderWorkflowEditor() {
        $this->template->links = $this->createBackFullUrl('SuperAdmin:Processes', 'list');

        $this->template->current_user_id = sprintf('$UID_%s$', $this->getUserId());
        $this->template->group_id = sprintf('$GID_%s$', $this->app->groupManager->getGroupByTitle('containerManagers')->groupId);
    }

    protected function createComponentWorkflowEditor(HttpRequest $request) {
        $form = $this->componentFactory->getFormBuilder();

        $params = [
            'processId' => $request->get('processId'),
            'uniqueProcessId' => $request->get('uniqueProcessId')
        ];

        if($request->get('oldProcessId') !== null) {
            $params['oldProcessId'] = $request->get('oldProcessId');
        }

        $form->setAction($this->createURL('finalSaveProcess', $params));
        
        $form->addLabel('workflowDefinition_lbl', 'Workflow definition:')
            ->setRequired();

        $form->addTextArea('workflowDefinition')
            ->setRequired()
            ->setLines(20);

        $form->addButton('Verify')
            ->setOnClick('sendVerify()');

        $form->addSubmit('Save');

        $arb = new AjaxRequestBuilder();

        $arb->setAction($this, 'editorVerify')
            ->setMethod('POST')
            ->setHeader(['code' => '_code'])
            ->setFunctionName('editorVerify')
            ->setFunctionArguments(['_code'])
            ->updateHTMLElement('workflow-view', 'workflow');

        $form->addScript($arb);

        $code = '
            function sendVerify() {
                const _code = $("#workflowDefinition").val();

                if(_code == "") {
                    alert("No workflow definition entered.");
                    return;
                }

                var _json = "";

                try {
                    _json = JSON.parse(_code);

                    editorVerify(JSON.stringify(_json));
                } catch(exception) {
                    alert("Could not parse JSON. Reason: " + exception);
                }
            }
        ';

        $form->addScript($code);

        return $form;
    }

    private function getWorkflowFromJson(array $decodedJson) {
        $workflow = $decodedJson['workflow'];

        $workflowUsers = [];
        $lastUser = null;

        foreach($workflow as $w) {
            $name = $w['name'];

            switch($name) {
                case '$CURRENT_USER$':
                    if($lastUser === null || ($lastUser !== null && $lastUser != '$CURRENT_USER$')) {
                        $workflowUsers[] = '$CURRENT_USER$';
                        $lastUser = '$CURRENT_USER$';
                    }
                    break;

                case '$ACCOUNTANTS$':
                    if($lastUser === null || ($lastUser !== null && $lastUser != '$ACCOUNTANTS$')) {
                        $workflowUsers[] = '$ACCOUNTANTS$';
                        $lastUser = '$ACCOUNTANTS$';
                    }
                    break;

                case '$ARCHIVISTS$':
                    if($lastUser === null || ($lastUser !== null && $lastUser != '$ARCHIVISTS$')) {
                        $workflowUsers[] = '$ARCHIVISTS$';
                        $lastUser = '$ARCHIVISTS$';
                    }
                    break;

                case '$PROPERTY_MANAGERS$':
                    if($lastUser === null || ($lastUser !== null && $lastUser != '$PROPERTY_MANAGERS$')) {
                        $workflowUsers[] = '$PROPERTY_MANAGERS$';
                        $lastUser = '$PROPERTY_MANAGERS$';
                    }
                    break;

                case '$CURRENT_USER_SUPERIOR$':
                    if($lastUser === null || ($lastUser !== null && $lastUser != '$CURRENT_USER_SUPERIOR$')) {
                        $workflowUsers[] = '$CURRENT_USER_SUPERIOR$';
                        $lastUser = '$CURRENT_USER_SUPERIOR$';
                    }
                    break;
            }
        }

        return $workflowUsers;
    }

    private function getWorkflowActions(array $decodedJson) {
        $workflow = $decodedJson['workflow'];

        $workflowActions = [];

        foreach($workflow as $w) {
            if(!array_key_exists('actions', $w)) {
                throw new GeneralException('Attribute \'action\' is not defined.');
            }
            
            $name = $w['name'];
            $actions = $w['actions'];

            $okActions = [];

            foreach($actions as $action) {
                if(!in_array($action, ProcessInstanceOperations::getAllConstants())) continue;

                $okActions[] = $action;
            }

            $workflowActions[$name] = $okActions;
        }

        return $workflowActions;
    }

    public function actionEditorVerify() {
        $jsonCode = $this->httpRequest->get('code');

        $decodedJson = json_decode($jsonCode, true);

        if($decodedJson === null) {
            return new JsonResponse(['error' => '1', 'errorMsg' => 'The form JSON entered is incorrect.']);
        }

        $workflowUsers = $this->getWorkflowFromJson($decodedJson);
        $workflowActions = $this->getWorkflowActions($decodedJson);

        $workflowUsersPrettified = '<ol>';
        foreach($workflowUsers as $workflowUser) {
            $workflowUsersPrettified .= '
                <li>' . $workflowUser . ' (will be filled on activation)<ul>';

            foreach($workflowActions[$workflowUser] as $action) {
                $workflowUsersPrettified .= '<li>' . ProcessInstanceOperations::toString($action) . '</li>';
            }

            $workflowUsersPrettified .= '</ul></li>';
        }
        $workflowUsersPrettified .= '</ol>';

        try {
            return new JsonResponse(['workflow' => $workflowUsersPrettified]);
        } catch(AException $e) {
            return new JsonResponse(['error' => '1', 'errorMsg' => $e->getMessage()]);
        }
    }

    protected function createComponentWorkflowVariablesList(HttpRequest $request) {
        $list = $this->componentFactory->getListBuilder();

        $list->setListName('workflowVariablesList');

        $variables = [];

        $addVariable = function(string $name, string $description) use (&$variables) {
            $variables[] = [
                'name' => $name,
                'description' => $description
            ];
        };

        $addVariable('$CURRENT_USER$', 'Current user');
        $addVariable('$ACCOUNTANTS$', 'Accountants group');
        $addVariable('$ARCHIVISTS$', 'Archivists group');
        $addVariable('$PROPERTY_MANAGERS$', 'Property managers');
        $addVariable('$CURRENT_USER_SUPERIOR$', 'Current user\'s superior');

        $list->setDataSource($variables);

        $list->addColumnText('name', 'Name');
        $list->addColumnText('description', 'Description');

        return $list;
    }

    public function handleFinalSaveProcess(FormRequest $fr) {
        try {
            $this->app->processRepository->beginTransaction(__METHOD__);

            $this->app->processRepository->commit($this->getUserId(), __METHOD__);
        } catch(AException $e) {
            $this->app->processRepository->rollback(__METHOD__);
        }
    }

    public function handlePublishProcess(FormRequest $fr) {
        try {
            $oldProcessId = null;
            if($this->httpRequest->get('processId') !== null) {
                $oldProcessId = $this->httpRequest->get('processId');
            }

            $formdata = $this->httpRequest->get('formdata');
            $formdata = json_decode(base64_decode($formdata), true);

            $title = $formdata['title'];
            $description = $formdata['description'];

            $code = base64_encode($fr->formDefinition);

            $this->app->processRepository->beginTransaction(__METHOD__);

            // add new version
            [$processId, $uniqueProcessId] = $this->app->processManager->createNewProcess($title, $description, $this->getUserId(), $code, $oldProcessId);

            // remove old version from distribution
            if($oldProcessId !== null) {
                $this->app->processManager->updateProcess($oldProcessId, ['status' => ProcessStatus::NOT_IN_DISTRIBUTION]);
            }

            $containers = $this->app->containerManager->getAllContainers(true, true);

            foreach($containers as $container) {
                /**
                 * @var \App\Entities\ContainerEntity $container
                 */

                if(!$container->isInDistribution()) continue;

                $dbConn = $this->app->dbManager->getConnectionToDatabase($container->getDefaultDatabase()->getName());

                $processRepository = new ProcessRepository($dbConn, $this->logger, $this->app->userRepository->transactionLogRepository);

                $processRepository->removeCurrentDistributionProcessFromDistributionForUniqueProcessId($uniqueProcessId);

                $processRepository->addNewProcess($processId, $uniqueProcessId, $title, $description, $code, $this->getUserId(), ContainerProcessStatus::NEW);
            }

            $this->app->processRepository->commit($this->getUserId(), __METHOD__);

            $this->flashMessage('Successfully created a new process.', 'success');
        } catch(AException $e) {
            $this->app->processRepository->rollback(__METHOD__);

            $this->flashMessage('Could not create a new process. Reason: ' . $e->getMessage(), 'error', 10);
        }

        //$this->redirect($this->createFullURL('SuperAdmin:Processes', 'list'));
        $this->redirect($this->createURL('workflowEditor', ['processId' => $processId]));
    }
}

?>