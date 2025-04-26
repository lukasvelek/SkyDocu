<?php

namespace App\Modules\UserModule;

use App\Components\ProcessSelect\ProcessSelect;
use App\Components\ProcessViewsSidebar\ProcessViewsSidebar;
use App\Constants\Container\ProcessInstanceOfficerTypes;
use App\Constants\Container\ProcessInstanceStatus;
use App\Core\Http\FormRequest;
use App\Core\Http\HttpRequest;
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

        $process = $this->processManager->getProcessById($processId);

        $this->redirect($this->createURL('processForm', [
            'processId' => $processId
        ]));
    }

    public function renderProcessForm() {
        $process = $this->processManager->getProcessById($this->httpRequest->get('processId'));

        $this->template->process_title = $process->title;

        $this->template->links = $this->createBackUrl('select');
    }

    protected function createComponentProcessForm(HttpRequest $request) {
        $process = $this->processManager->getProcessById($request->get('processId'));

        $form = $this->componentFactory->getFormBuilder();
        $form->setAction($this->createURL('submitProcessForm', ['processId' => $process->processId]));

        $json = json_decode(base64_decode($process->form), true);

        $json2Fb = new JSON2FB($form, $json);
        $json2Fb->setSkipAttributes(['action']);
        $json2Fb->addSubmitButton('Submit');

        $form = $json2Fb->getFormBuilder();

        return $form;
    }

    public function handleSubmitProcessForm(FormRequest $fr) {
        $processId = $this->httpRequest->get('processId');

        $process = $this->processManager->getProcessById($processId);

        $formData = serialize($fr->getData());

        $instanceData = [
            'processId' => $processId,
            'userId' => $this->getUserId(),
            'data' => $formData,
            'currentOfficerType' => ProcessInstanceOfficerTypes::NONE,
            'status' => ProcessInstanceStatus::NEW
        ];

        // create process instance

        try {
            $this->processInstanceRepository->beginTransaction(__METHOD__);

            $instanceId = $this->processInstanceManager->startNewInstanceFromArray($instanceData);

            $this->processInstanceRepository->commit($this->getUserId(), __METHOD__);
        } catch(AException $e) {
            $this->processInstanceRepository->rollback(__METHOD__);

            $this->flashMessage('Could not start new process instance. Reason: ' . $e->getMessage(), 'error', 10);

            $this->redirect($this->createURL('select'));
        }

        $workflow = unserialize($process->workflow);

        // evaluate new officer
        [$officer, $type] = $this->processInstanceManager->evaluateNextProcessInstanceOfficer($workflow, $this->getUserId(), 0);

        $formData = $fr->getData();
        $formData['workflowIndex'] = 0;
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
}

?>