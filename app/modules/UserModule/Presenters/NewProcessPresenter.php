<?php

namespace App\Modules\UserModule;

use App\Components\ProcessSelect\ProcessSelect;
use App\Components\ProcessViewsSidebar\ProcessViewsSidebar;
use App\Constants\Container\ProcessInstanceOfficerTypes;
use App\Constants\Container\ProcessInstanceOperations;
use App\Constants\Container\ProcessInstanceStatus;
use App\Core\Http\FormRequest;
use App\Core\Http\HttpRequest;
use App\Core\Http\JsonResponse;
use App\Exceptions\AException;
use App\UI\FormBuilder2\JSON2FB;

class NewProcessPresenter extends AUserPresenter {
    public function __construct() {
        parent::__construct('NewProcessPresenter', 'New process');
    }

    public function renderSelect() {}

    protected function createComponentProcessViewsSidebar(HttpRequest $request) {
        /**
         * @var ProcessViewsSidebar $sidebar
         */
        $sidebar = $this->componentFactory->createComponentInstanceByClassName(ProcessViewsSidebar::class);

        $sidebar->setNewProcessActive();

        return $sidebar;
    }

    protected function createComponentProcessSelect(HttpRequest $request) {
        $processSelect = $this->componentFactory->createComponentInstanceByClassName(ProcessSelect::class, [
            $this->processManager,
            $this->processRepository
        ]);

        return $processSelect;
    }

    public function handleStartProcess() {
        $processId = $this->httpRequest->get('processId');

        $instanceId = $this->processInstanceManager->generateUniqueInstanceId();

        $this->redirect($this->createURL('processForm', [
            'processId' => $processId,
            'instanceId' => $instanceId
        ]));
    }

    public function renderProcessForm() {
        $process = $this->processManager->getProcessById($this->httpRequest->get('processId'));

        $this->template->process_title = $process->title;
        $this->template->links = $this->createBackUrl('select');
    }

    protected function createComponentProcessForm(HttpRequest $request) {
        $instanceId = $request->get('instanceId');

        $process = $this->processManager->getProcessById($request->get('processId'));

        $form = $this->componentFactory->getFormBuilder();
        $form->setAction($this->createURL('submitProcessForm', ['processId' => $process->processId, 'instanceId' => $instanceId]));

        $definition = json_decode(base64_decode($process->definition), true);

        $json = json_decode($definition['forms'][0]['form'], true);

        $json2Fb = new JSON2FB($form, $json, $this->containerId);
        $json2Fb->setSkipAttributes(['action']);
        $json2Fb->addSubmitButton('Submit');
        $json2Fb->setCustomUrlParams(['processId' => $process->processId, 'instanceId' => $instanceId]);
        
        $form = $json2Fb->getFormBuilder();
        
        if($process->title == 'Invoice') {
            $form->setCallReducerOnChange(false);
        }

        return $form;
    }

    public function handleSubmitProcessForm(FormRequest $fr) {
        $processId = $this->httpRequest->get('processId');
        $instanceId = $this->httpRequest->get('instanceId');

        $process = $this->processManager->getProcessById($processId);

        $description = sprintf('New %s process instance', $process->title);

        $index = 0;

        $definition = json_decode(base64_decode($process->definition), true);
        $forms = $definition['forms'];

        $workflow = [];
        foreach($forms as $form) {
            $workflow[] = $form['actor'];
        }

        if(($index + 1) <= count($workflow)) {
            foreach($forms as $form) {
                $_form = json_decode($form['form'], true);

                if($form['actor'] == $workflow[$index]) {
                    if(array_key_exists('instanceDescription', $_form)) {
                        //$description = $_form['instanceDescription'];
                    }
                }
            }
        }

        $formData = serialize($fr->getData());

        $instanceData = [
            'processId' => $processId,
            'userId' => $this->getUserId(),
            'data' => $formData,
            'currentOfficerType' => ProcessInstanceOfficerTypes::NONE,
            'status' => ProcessInstanceStatus::NEW,
            'description' => $description
        ];

        // create process instance
        try {
            $this->processInstanceRepository->beginTransaction(__METHOD__);

            $this->processInstanceManager->startNewInstanceFromArray($instanceId, $instanceData);

            $this->processInstanceRepository->commit($this->getUserId(), __METHOD__);
        } catch(AException $e) {
            $this->processInstanceRepository->rollback(__METHOD__);

            $this->flashMessage('Could not start new process instance. Reason: ' . $e->getMessage(), 'error', 10);

            $this->redirect($this->createURL('select'));
        }

        $instance = $this->processInstanceManager->getProcessInstanceById($instanceId);

        // evaluate new officer
        [$officer, $type] = $this->processInstanceManager->evaluateNextProcessInstanceOfficer($instance, $workflow, $this->getUserId(), 0);

        $formData = $fr->getData();
        $formData['workflowIndex'] = 0;
        $formData['workflowHistory'][][$this->getUserId()] = [
            'operation' => ProcessInstanceOperations::CREATE,
            'date' => date('Y-m-d H:i:s')
        ];
        $formData = serialize($formData);

        $instanceData = [
            'currentOfficerId' => $officer,
            'currentOfficerType' => $type,
            'status' => ProcessInstanceStatus::IN_PROGRESS,
            'data' => $formData
        ];

        try {
            $this->processInstanceRepository->beginTransaction(__METHOD__);

            $this->processInstanceManager->updateInstance($instanceId, $instanceData);

            $this->processInstanceRepository->commit($this->getUserId(), __METHOD__);

            $this->flashMessage('New process instance started successfully.', 'success');
        } catch(AException $e) {
            $this->processInstanceRepository->rollback(__METHOD__);

            $this->flashMessage('Could not start new process instance. Reason: ' . $e->getMessage(), 'error', 10);
        }

        $this->redirect($this->createFullURL('User:Processes', 'list', ['view' => 'waitingForMe']));
    }

    // PROCESS HANDLERS
    public function actionSearchCompanies() { // INVOICE PROCESS
        $processId = $this->httpRequest->get('processId');
        $query = $this->httpRequest->get('query');
        
        $uniqueProcessId = $this->processManager->getUniqueProcessIdForProcessId($processId);

        $values = $this->processMetadataManager->searchMetadataValuesForUniqueProcessId($uniqueProcessId, 'companies', $query);

        $options = [];
        foreach($values as $value) {
            $options[] = '<option value="' . $value->metadataKey . '">' . $value->title . '</option>';
        }

        return new JsonResponse(['data' => implode('', $options)]);
    }

    public function actionSearchProcesses() {
        $processId = $this->httpRequest->get('processId');
        $query = $this->httpRequest->get('query');

        $qb = $this->processRepository->composeQueryForAvailableProcesses();

        $qb->andWhere('title LIKE ?', ["%$query%"])
            ->execute();
        
        $results = [];
        while($row = $qb->fetchAssoc()) {
            $results[] = '<option value="' . $row['processId'] . '">' . $row['title'] . '</option>';
        }

        return new JsonResponse(['data' => implode(''. $results)]);
    }

    public function actionSearchDocuments() {
        $processId = $this->httpRequest->get('processId');
        $query = $this->httpRequest->get('query');

        $qb = $this->documentRepository->composeQueryForDocuments();

        $qb->andWhere('title LIKE ?', ["%$query%"])
            ->execute();

        $results = [];
        while($row = $qb->fetchAssoc()) {
            $results[] = '<option value="' . $row['documentId'] . '">' . $row['title'] . '</option>';
        }

        return new JsonResponse(['data' => implode('', $results)]);
    }
}

?>