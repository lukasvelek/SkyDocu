<?php

namespace App\Modules\UserModule;

use App\Constants\Container\CustomMetadataTypes;
use App\Constants\Container\DocumentStatus;
use App\Exceptions\AException;
use App\UI\FormBuilder\FormBuilder;
use App\UI\FormBuilder\FormResponse;

class CreateDocumentPresenter extends AUserPresenter {
    public function __construct() {
        parent::__construct('CreateDocumentPresenter', 'Create document');
    }

    private function addCustomMetadataFormControls(FormBuilder $form, string $folderId) {
        $customMetadatas = $this->documentManager->getCustomMetadataForFolder($folderId);

        foreach($customMetadatas as $metadataId => $metadata) {
            switch($metadata->type) {
                case CustomMetadataTypes::BOOL:
                    $form->addCheckbox($metadata->title, $metadata->guiTitle . ':');
                    break;

                case CustomMetadataTypes::DATETIME:
                    $form->addDatetime($metadata->title, $metadata->guiTitle . ':');
                    break;

                case CustomMetadataTypes::ENUM:
                    $selectValuesDb[$metadata->title] = $this->documentManager->getMetadataValues($metadataId);
                    $values = [];
                    foreach($selectValuesDb as $key => $title) {
                        $values[] = [
                            'value' => $key,
                            'text' => $title
                        ];
                    }
                    $form->addSelect($metadata->title, $metadata->guiTitle, $values);
                    break;

                case CustomMetadataTypes::NUMBER:
                    $form->addNumberInput($metadata->title, $metadata->guiTitle . ':');
                    break;

                case CustomMetadataTypes::TEXT:
                    $form->addTextInput($metadata->title, $metadata->guiTitle . ':');
                    break;
            }
        }

        return $form;
    }

    public function handleForm() {
        $folderId = $this->httpGet('folderId', true);

        // DOCUMENT CLASSES
        $classesDb = $this->documentManager->getDocumentClassesForDocumentCreateForUser($this->getUserId());

        $classes = [];
        foreach($classesDb as $id => $text) {
            $classes[] = [
                'value' => $id,
                'text' => $text
            ];
        }
        // END OF DOCUMENT CLASSES

        $form = new FormBuilder();

        $form->setMethod()
            ->setAction($this->createURL('processForm', ['folderId' => $folderId]))

            ->addTextInput('title', 'Title:', null, true)
            ->addTextArea('description', 'Description:')
            ->addSelect('class', 'Class:', $classes, true)
        ;

        $form = $this->addCustomMetadataFormControls($form, $folderId);

        $form->addSubmit('Create');

        $this->saveToPresenterCache('form', $form);
    }

    public function renderForm() {
        $this->template->form = $this->loadFromPresenterCache('form');
    }

    public function handleProcessForm(FormResponse $fr) {
        $folderId = $this->httpGet('folderId', true);

        $customMetadatas = $this->documentManager->getCustomMetadataForFolder($folderId);

        $metadataValues = [
            'title' => $fr->title,
            'classId' => $fr->class,
            'authorUserId' => $this->getUserId(),
            'status' => DocumentStatus::NEW,
            'folderId' => $folderId
        ];

        if(isset($fr->description)) {
            $metadataValues['description'] = $fr->description;
        }

        $customMetadataValues = [];
        foreach($customMetadatas as $metadataId => $metadata) {
            if(isset($fr->{$metadata->title})) {
                $customMetadataValues[$metadataId] = $fr->{$metadata->title};
            }
        }

        try {
            $this->documentClassRepository->beginTransaction(__METHOD__);

            $this->documentManager->createNewDocument($metadataValues, $customMetadataValues);

            $this->documentClassRepository->commit($this->getUserId(), __METHOD__);

            $this->flashMessage('Document created.', 'success');
        } catch(AException $e) {
            $this->documentClassRepository->rollback(__METHOD__);
            
            $this->flashMessage('Could not create new document. Reason: ' . $e->getMessage(), 'error', 10);
        }

        $this->redirect($this->createFullURL('User:Documents', 'list', ['folderId' => $folderId]));
    }
}

?>