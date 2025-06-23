<?php

namespace App\Modules\AdminModule;

use App\Constants\Container\ProcessStatus;
use App\Constants\ProcessColorCombos;
use App\Core\AjaxRequestBuilder;
use App\Core\Http\FormRequest;
use App\Core\Http\HttpRequest;
use App\Core\Http\JsonResponse;
use App\Exceptions\AException;
use App\Helpers\LinkHelper;
use App\Helpers\ProcessEditorHelper;
use App\UI\FormBuilder2\JSON2FB;
use App\UI\HTML\HTML;
use App\UI\LinkBuilder;
use App\UI\ListBuilder\ArrayRow;
use App\UI\ListBuilder\ListAction;
use App\UI\ListBuilder\ListRow;

class ProcessEditorPresenter extends AAdminPresenter {
    public function __construct() {
        parent::__construct('ProcessEditorPresenter', 'Process editor');
    }

    public function renderForm() {
        $this->template->links = $this->createBackFullUrl('Admin:Processes', 'list');
    }

    protected function createComponentProcessForm() {
        $process = null;
        if($this->httpRequest->get('processId') !== null && $this->httpRequest->get('uniqueProcessId') !== null) {
            $processId = $this->httpRequest->get('processId');
            $process = $this->processManager->getProcessEntityById($processId);
        }

        $form = $this->componentFactory->getFormBuilder();

        $params = [];

        if($process !== null) {
            $params = [
                'processId' => $process->getId(),
                'uniqueProcessId' => $process->getUniqueProcessId()
            ];
        }

        $form->setAction($this->createURL('formSubmit', $params));

        $title = $form->addTextInput('title', 'Title:')
            ->setRequired();

        if($process !== null) {
            $title->setValue($process->getTitle());
        }

        $description = $form->addTextArea('description', 'Description:')
            ->setRequired();

        if($process !== null) {
            $description->setContent($process->getDescription());
        }
            
        $colors = [];
        foreach(ProcessColorCombos::getAll() as $key => $value) {
            $color = [
                'value' => $key,
                'text' => $value
            ];

            if($process !== null) {
                if($process->getColorCombo() !== null && $process->getColorCombo() == $key) {
                    $color['selected'] = 'selected';
                } else if($process->getColorCombo() === null && $key == ProcessColorCombos::GREEN) {
                    $color['selected'] = 'selected';
                }
            }

            $colors[] = $color;
        }

        $form->addSelect('colorCombo', 'Color:')
            ->setRequired()
            ->addRawOptions($colors);

        $form->addSubmit('Go to editor');

        return $form;
    }

    public function handleFormSubmit(FormRequest $fr) {
        $title = $fr->title;
        $description = $fr->description;
        $colorCombo = $fr->colorCombo;

        $oldProcessId = null;
        if($this->httpRequest->get('processId') !== null) {
            $oldProcessId = $this->httpRequest->get('processId');
        }

        $definition = [
            'colorCombo' => $colorCombo
        ];

        if($oldProcessId !== null) {
            $oldProcess = $this->processManager->getProcessEntityById($oldProcessId);

            if($oldProcess->getTitle() == $title &&
                $oldProcess->getDescription() == $description &&
                $oldProcess->getColorCombo() == $colorCombo) {
                // it is the same - no saving

                $params = [
                    'processId' => $oldProcessId,
                    'uniqueProcessId' => $oldProcess->getUniqueProcessId()
                ];

                $this->redirect($this->createURL('workflowList', $params));
            }

            $definition = $oldProcess->getDefinition();
            $definition['colorCombo'] = $colorCombo;
        }

        try {
            $this->processRepository->beginTransaction(__METHOD__);

            [$processId, $uniqueProcessId] = $this->processManager->createNewProcess(
                $title,
                $description,
                $this->getUserId(),
                $definition,
                $oldProcessId
            );

            $params = [
                'processId' => $processId,
                'uniqueProcessId' => $uniqueProcessId
            ];

            if($oldProcessId !== null) {
                $params['oldProcessId'] = $oldProcessId;
            } else {
                $params['isNew'] = 1;
            }

            $this->processRepository->commit($this->getUserId(), __METHOD__);

            $this->flashMessage('Successfully created a new process. Now, you have to define the workflow.', 'success');

            $this->redirect($this->createURL('workflowList', $params));
        } catch(AException $e) {
            $this->processRepository->rollback(__METHOD__);

            $this->flashMessage('Could not create a new process. Reason: ' . $e->getMessage(), 'error', 10);

            $this->redirect($this->createFullURL('Admin:Processes', 'list'));
        }
    }

    public function renderWorkflowList() {
        $params = [
            'processId' => $this->httpRequest->get('processId'),
            'uniqueProcessId' => $this->httpRequest->get('uniqueProcessId')
        ];

        if($this->httpRequest->get('oldProcessId') !== null) {
            $params['oldProcessId'] = $this->httpRequest->get('oldProcessId');
        }

        $links = [
            $this->createBackFullUrl('Admin:Processes', 'list'),
            LinkBuilder::createSimpleLink('New workflow step', $this->createURL('editor2', $params), 'link')
        ];

        $process = $this->processManager->getProcessEntityById($this->httpRequest->get('processId'));

        $previousVersion = null;
        try {
            $previousVersion = $this->processManager->getPreviousVersionForProcessId($process->getId(), true);
        } catch(AException $e) {}

        $workflow = $process->getDefinition()['forms'] ?? [];

        $showPublishLink = false;
        if(count($workflow) > 0) { // workflow must not bet empty
            if($previousVersion !== null) {
                // previous version exists
                if($process->getStatus() == ProcessStatus::NEW && $previousVersion->getStatus() == ProcessStatus::IN_DISTRIBUTION) {
                    // previous version is in distribution and the current is new
                    $showPublishLink = true;
                } else if($process->getStatus() == ProcessStatus::NEW && $previousVersion->getStatus() == ProcessStatus::NOT_IN_DISTRIBUTION) {
                    // previous version is not in distribution and the current is new
                    // we must check if there is a version before the current one that is in distribution
                    $run = true;
                    $_pv = $previousVersion;
                    while($run) {
                        try {
                            $_pv = $this->app->processManager->getPreviousVersionForProcessId($_pv->getId(), true);
                        } catch(AException $e) {
                            $run = false;
                        }

                        if($_pv->getStatus() == ProcessStatus::IN_DISTRIBUTION) {
                            $showPublishLink = true;
                            $run = false;
                        }
                    }
                }
            } else {
                // previous version does not exist
                if($process->getStatus() == ProcessStatus::NEW) {
                    // current version is new
                    $showPublishLink = true;
                }
            }
        }

        if($showPublishLink) {
            $links[] = LinkBuilder::createSimpleLink('Publish', $this->createURL('publish', $params), 'link');
        }

        $this->template->links = LinkHelper::createLinksFromArray($links);
    }

    protected function createComponentWorkflowList(HttpRequest $request) {
        $processId = $request->get('processId');

        $process = $this->processManager->getProcessEntityById($processId);
        $workflow = $process->getWorkflow();

        $count = count($workflow);

        $datasource = [];
        $i = 1;
        foreach($workflow as $w) {
            $datasource[] = [
                'workflow' => $w,
                'index' => $i
            ];
            $i++;
        }

        $params = [
            'processId' => $request->get('processId'),
            'uniqueProcessId' => $request->get('uniqueProcessId')
        ];

        if($request->get('oldProcessId') !== null) {
            $params['oldProcessId'] = $request->get('oldProcessId');
        }

        $list = $this->componentFactory->getListBuilder();

        $list->setListName('processWorkflowList');
        $list->setDataSource($datasource);

        $list->addColumnText('index', '#');
        $list->addColumnText('workflow', 'Workflow');

        $up = $list->addAction('up');
        $up->setTitle('&uarr;');
        $up->onCanRender[] = function(ArrayRow $row, ListRow $_row, ListAction &$action) use ($count) {
            $isFirst = $_row->index == 1;
            return $count > 1 && !$isFirst;
        };
        $up->onRender[] = function(mixed $primaryKey, ArrayRow $row, ListRow $_row, HTML $html) use ($params) {
            $_params = $params;
            $_params['primaryKey'] = $primaryKey;
            $_params['direction'] = 'up';

            $el = HTML::el('a');
            $el->href($this->createURLString('moveWorkflowStep', $_params))
                ->text('&uarr;')
                ->class('grid-link');

            return $el;
        };

        $down = $list->addAction('down');
        $down->setTitle('&darr;');
        $down->onCanRender[] = function(ArrayRow $row, ListRow $_row, ListAction &$action) use ($count) {
            $isLast = $_row->index == $count;
            return $count > 1 && !$isLast;
        };
        $down->onRender[] = function(mixed $primaryKey, ArrayRow $row, ListRow $_row, HTML $html) use ($params) {
            $_params = $params;
            $_params['primaryKey'] = $primaryKey;
            $_params['direction'] = 'down';

            $el = HTML::el('a');
            $el->href($this->createURLString('moveWorkflowStep', $_params))
                ->text('&darr;')
                ->class('grid-link');

            return $el;
        };

        $view = $list->addAction('view');
        $view->setTitle('View');
        $view->onCanRender[] = function() {
            return true;
        };
        $view->onRender[] = function(mixed $primaryKey, ArrayRow $row, ListRow $_row, HTML $html) use ($params) {
            $_params = $params;
            $_params['primaryKey'] = $primaryKey;
            $_params['operation'] = 'view';

            $el = HTML::el('a');
            $el->href($this->createURLString('editor2', $_params))
                ->text('View')
                ->class('grid-link');

            return $el;
        };

        $edit = $list->addAction('Copy');
        $edit->setTitle('Copy');
        $edit->onCanRender[] = function() {
            return true;
        };
        $edit->onRender[] = function(mixed $primaryKey, ArrayRow $row, ListRow $_row, HTML $html) use ($params) {
            $_params = $params;
            $_params['primaryKey'] = $primaryKey;

            $el = HTML::el('a');
            $el->href($this->createURLString('editor2', $_params))
                ->text('Copy')
                ->class('grid-link');

            return $el;
        };

        $delete = $list->addAction('delete');
        $delete->setTitle('Delete');
        $delete->onCanRender[] = function() {
            return true;
        };
        $delete->onRender[] = function(mixed $primaryKey, ArrayRow $row, ListRow $_row, HTML $html) use ($params) {
            $_params = $params;
            $_params['primaryKey'] = $primaryKey;

            $el = HTML::el('a');
            $el->href($this->createURLString('deleteWorkflowStep', $_params))
                ->text('Delete')
                ->class('grid-link');

            return $el;
        };

        return $list;
    }

    public function handleMoveWorkflowStep() {
        $direction = $this->httpRequest->get('direction');
        $primaryKey = $this->httpRequest->get('primaryKey');
        $processId = $this->httpRequest->get('processId');

        $params = [
            'processId' => $this->httpRequest->get('processId'),
            'uniqueProcessId' => $this->httpRequest->get('uniqueProcessId')
        ];

        if($this->httpRequest->get('oldProcessId') !== null) {
            $params['oldProcessId'] = $this->httpRequest->get('oldProcessId');
        }

        $process = $this->processManager->getProcessEntityById($processId);

        $definition = $process->getDefinition();

        $forms = $definition['forms'];

        $nextPrimaryKey = $direction == 'up' ? ($primaryKey - 1) : ($primaryKey + 1);

        $newForms = [];
        foreach($forms as $index => $form) {
            if($index == $primaryKey) continue;
            $newForms[] = $form;
        }

        array_splice($newForms, $nextPrimaryKey, 0, [$forms[$primaryKey]]);

        $definition['forms'] = $newForms;

        try {
            $this->processRepository->beginTransaction(__METHOD__);

            $this->processManager->updateProcess($processId, [
                'definition' => base64_encode(json_encode($definition))
            ]);

            $this->processRepository->commit($this->getUserId(), __METHOD__);

            $this->flashMessage('Successfully moved workflow step ' . $direction . '.', 'success');
        } catch(AException $e) {
            $this->processRepository->rollback(__METHOD__);

            $this->flashMessage('Could not move workflow step ' . $direction . '. Reason: ' . $e->getMessage(), 'error', 10);
        }

        $this->redirect($this->createURL('workflowList', $params));
    }

    public function handleDeleteWorkflowStep() {
        $primaryKey = $this->httpRequest->get('primaryKey');
        $processId = $this->httpRequest->get('processId');

        $params = [
            'processId' => $this->httpRequest->get('processId'),
            'uniqueProcessId' => $this->httpRequest->get('uniqueProcessId')
        ];

        if($this->httpRequest->get('oldProcessId') !== null) {
            $params['oldProcessId'] = $this->httpRequest->get('oldProcessId');
        }

        $process = $this->processManager->getProcessEntityById($processId);

        $definition = $process->getDefinition();

        $forms = $definition['forms'];
        
        $newForms = [];
        foreach($forms as $index => $form) {
            if($index == $primaryKey) continue;

            $newForms[] = $form;
        }

        $definition['forms'] = $newForms;

        try {
            $this->processRepository->beginTransaction(__METHOD__);

            $this->processManager->updateProcess($processId, [
                'definition' => base64_encode(json_encode($definition))
            ]);

            $this->processRepository->commit($this->getUserId(), __METHOD__);

            $this->flashMessage('Successfully deleted workflow step.', 'success');
        } catch(AException $e) {
            $this->processRepository->rollback(__METHOD__);

            $this->flashMessage('Could not delete workflow step. Reason: ' . $e->getMessage(), 'error', 10);
        }

        $this->redirect($this->createURL('workflowList', $params));
    }

    public function renderEditor2() {
        $params = [
            'processId' => $this->httpRequest->get('processId'),
            'uniqueProcessId' => $this->httpRequest->get('uniqueProcessId')
        ];

        if($this->httpRequest->get('oldProcessId') !== null) {
            $params['oldProcessId'] = $this->httpRequest->get('oldProcessId');
        }

        $this->template->links = $this->createBackUrl('workflowList', $params);
    }

    protected function createComponentProcessWorkflowEditor(HttpRequest $request) {
        $params = [
            'processId' => $request->get('processId'),
            'uniqueProcessId' => $request->get('uniqueProcessId')
        ];

        if($request->get('oldProcessId') !== null) {
            $params['oldProcessId'] = $request->get('oldProcessId');
        }

        $process = $this->processManager->getProcessEntityById($request->get('processId'));

        $isFirst = empty($process->getWorkflow());

        $primaryKey = null;
        $operation = null;
        if($request->get('primaryKey') !== null) {
            $primaryKey = $request->get('primaryKey');
            //$params['primaryKey'] = $primaryKey;
        }
        if($request->get('operation') !== null) {
            $operation = $request->get('operation');
            //$params['operation'] = $operation;
        }

        $form = $this->componentFactory->getFormBuilder();

        $form->setAction($this->createURL('saveWorkflowEditor', $params));

        $actor = $form->addTextInput('actor', 'Actor:')
            ->setRequired();

        if($primaryKey !== null) {
            $actor->setValue($process->getDefinition()['forms'][$primaryKey]['actor']);

            if($operation == 'view') {
                $actor->setReadonly();
            }
        }

        $form->addLabel('formDefinitionLbl', 'Form definition:')
            ->setRequired();

        $fd = $form->addTextArea('formDefinition')
            ->setRequired()
            ->setLines(20);

        if($primaryKey !== null) {
            $fd->setContent($process->getDefinition()['forms'][$primaryKey]['form']);

            if($operation == 'view') {
                $fd->setReadonly();
            }
        }

        if($operation != 'view') {
            $form->addButton('Check form')
                ->setOnClick('sendLiveview()');

            $form->addSubmit('Save');
        }

        $arb = new AjaxRequestBuilder();

        $arb->setAction($this, 'editorLiveview2')
            ->setMethod('POST')
            ->setHeader(['code' => '_code', 'isFirst' => '_isFirst'])
            ->setFunctionName('editorLiveview')
            ->setFunctionArguments(['_code', '_isFirst'])
            ->updateHTMLElement('form-live-view', 'form');

        $form->addScript($arb);

        $arb = new AjaxRequestBuilder();

        $arb->setAction($this, 'editorServiceLiveview')
            ->setMethod('POST')
            ->setHeader(['code' => '_code'])
            ->setFunctionName('editorServiceLiveview')
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

                const _actor = $("#actor").val();

                if(_actor == "$SERVICE_USER$") {
                    alert("No form is used for service steps.");
                    return;
                }

                var _json = "";

                if(_actor == "$SERVICE_USER$") {
                    try {
                        _json = JSON.parse(_code);

                        editorServiceLiveview(JSON.stringify(_json));
                    } catch(exception) {
                        alert("Could not parse JSON. Reason: " + exception);
                    }

                    return;
                }

                try {
                    _json = JSON.parse(_code);

                    $("#form-live-view").html("<div id=\"center\"><img src=\"resources/loading.gif\" width=\"64px\"></div>");

                    editorLiveview(JSON.stringify(_json), ' . ($isFirst ? 'true' : 'false') . ');
                } catch(exception) {
                    alert("Could not parse JSON. Reason: " + exception);
                }
            }
        ';

        if($operation == 'view') {
            $code .= ' sendLiveview();';
        }

        $form->addScript($code);

        return $form;
    }

    public function handleSaveWorkflowEditor(FormRequest $fr) {
        $actor = $fr->actor;
        $form = $fr->formDefinition;

        $params = [
            'processId' => $this->httpRequest->get('processId'),
            'uniqueProcessId' => $this->httpRequest->get('uniqueProcessId')
        ];

        if($this->httpRequest->get('oldProcessId') !== null) {
            $params['oldProcessId'] = $this->httpRequest->get('oldProcessId');
        }

        $possibleActors = [
            '$CURRENT_USER$',
            '$ACCOUNTANTS$',
            '$ARCHIVISTS$',
            '$CURRENT_USER_SUPERIOR$',
            '$ADMINISTRATORS$',
            '$SERVICE_USER$',
            '$INSTANCE_AUTHOR$'
        ];

        if(!in_array($actor, $possibleActors) && !str_starts_with($actor, '$GID_') && !str_starts_with($actor, '$UID_')) {
            $this->flashMessage('Unknown actor defined. See docs for possible values.', 'error', 10);
            $this->redirect($this->createURL('editor2', $params));
        }

        $processId = $this->httpRequest->get('processId');

        $process = $this->processManager->getProcessEntityById($processId);

        $definition = $process->getDefinition();

        $definition['forms'][] = [
            'actor' => $actor,
            'form' => $form
        ];

        $data = [
            'definition' => base64_encode(json_encode($definition))
        ];

        try {
            $this->processRepository->beginTransaction(__METHOD__);

            $this->processManager->updateProcess($processId, $data);

            $this->processRepository->commit($this->getUserId(), __METHOD__);

            $this->flashMessage('Successfully saved process workflow step.', 'success');
        } catch(AException $e) {
            $this->processRepository->rollback(__METHOD__);

            $this->flashMessage('Could not save process workflow step. Reason: ' . $e->getMessage(), 'error', 10);
        }

        $this->redirect($this->createURL('workflowList', $params));
    }

    public function actionEditorLiveview2() {
        $jsonCode = $this->httpRequest->get('code');
        $isFirst = $this->httpRequest->get('isFirst') == 'true';

        $decodedJson = json_decode($jsonCode, true);

        if($decodedJson === null) {
            return new JsonResponse(['error' => '1', 'errorMsg' => 'The form JSON entered is incorrect.']);
        }

        $form = $this->componentFactory->getFormBuilder();

        $helper = new JSON2FB($form, $decodedJson, null);

        $helper->setSkipAttributes(['action']);
        $helper->addSkipElementAttributes('userSelect', 'containerId');
        $helper->addSkipElementAttributes('userSelectSearch', 'containerId');
        $helper->setEditor();
        if(!$isFirst) {
            $helper->checkForHandleButtons();
        } else {
            $helper->checkForNoHandleButtons();
        }

        try {
            $code = $helper->render();

            return new JsonResponse(['form' => $code]);
        } catch(AException $e) {
            return new JsonResponse(['error' => '1', 'errorMsg' => $e->getMessage()]);
        }
    }

    public function actionEditorServiceLiveview() {
        $jsonCode = $this->httpRequest->get('code');

        $decodedJson = json_decode($jsonCode, true);

        if($decodedJson === null) {
            return new JsonResponse(['error' => '1', 'errorMsg' => 'The form JSON entered is incorrect.']);
        }

        try {
            ProcessEditorHelper::checkServiceUserDefinition($decodedJson);
        } catch(AException $e) {
            return new JsonResponse(['error' => '1', 'errorMsg' => 'Form validation failed. Reason: ' . $e->getMessage()]);
        }

        $operations = ProcessEditorHelper::getServiceUserDefinitionUpdateOperations($decodedJson);

        $code = '<ul>';
        foreach($operations as $key => $value) {
            if($value === null) {
                $value = 'NULL';
            }
            $code .= '<li>' . $key . ' => ' . $value . '</li>';
        }
        $code .= '</ul>';

        return new JsonResponse(['form' => $code]);
    }

    public function handlePublish() {
        $processId = $this->httpRequest->get('processId');
        $uniqueProcessId = $this->httpRequest->get('uniqueProcessId');

        $oldProcessId = null;
        if($this->httpRequest->get('oldProcessId') !== null) {
            $oldProcessId = $this->httpRequest->get('oldProcessId');
        } else {
            $previousVersion = null;
            try {
                $previousVersion = $this->processManager->getPreviousVersionForProcessId($processId, true);
            } catch(AException $e) {}
            
            if($previousVersion !== null) {
                $oldProcessId = $previousVersion->getId();
            }
        }

        try {
            $this->processRepository->beginTransaction(__METHOD__);

            $fmText = 'Successfully published new process';

            if($oldProcessId !== null) {
                // Remove old process version from distribution
                $this->processManager->updateProcess($oldProcessId, [
                    'status' => ProcessStatus::OLD
                ]);

                $fmText .= ' version';
            }

            $fmText .= '.';

            // Add new process version to distribution
            $this->processManager->updateProcess($processId, [
                'status' => ProcessStatus::CURRENT
            ]);

            $this->processRepository->commit($this->getUserId(), __METHOD__);

            $this->flashMessage($fmText, 'success');
        } catch(AException $e) {
            $this->processRepository->rollback(__METHOD__);

            $this->flashMessage('Could not publish new process' . ($oldProcessId !== null ? ' version.' : '.') . ' Reason: ' . $e->getMessage(), 'error', 10);
        }

        $this->redirect($this->createFullURL('Admin:Processes', 'list'));
    }
}

?>