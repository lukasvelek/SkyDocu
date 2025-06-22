<?php

namespace App\Modules\AdminModule;

use App\Constants\Container\ProcessStatus;
use App\Constants\ProcessColorCombos;
use App\Core\Http\FormRequest;
use App\Exceptions\AException;

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
}

?>