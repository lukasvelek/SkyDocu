<?php

namespace App\Modules\UserModule;

use App\Constants\Container\CustomMetadataTypes;
use App\Constants\Container\DocumentStatus;
use App\Core\Http\HttpRequest;
use App\Exceptions\AException;
use App\UI\FormBuilder2\FormBuilder2;
use App\UI\FormBuilder\FormResponse;

class CreateDocumentPresenter extends AUserPresenter {
    public function __construct() {
        parent::__construct('CreateDocumentPresenter', 'Create document');
    }

    private function addCustomMetadataFormControls(FormBuilder2 $form, string $folderId) {
        $customMetadatas = $this->documentManager->getCustomMetadataForFolder($folderId);

        foreach($customMetadatas as $metadataId => $metadata) {
            switch($metadata->type) {
                case CustomMetadataTypes::BOOL:
                    $form->addCheckboxInput($metadata->title, $metadata->guiTitle . ':');
                    break;

                case CustomMetadataTypes::DATETIME:
                    $form->addDateTimeInput($metadata->title, $metadata->guiTitle . ':')
                        ->setRequired();
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
                    $form->addSelect($metadata->title, $metadata->guiTitle, $values, true);
                    break;

                case CustomMetadataTypes::NUMBER:
                    $form->addNumberInput($metadata->title, $metadata->guiTitle . ':', null, null, null, true);
                    break;

                case CustomMetadataTypes::TEXT:
                    $form->addTextInput($metadata->title, $metadata->guiTitle . ':', null, true);
                    break;
            }
        }

        return $form;
    }

    public function handleForm(?FormResponse $fr = null) {
        if($fr !== null) {
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

    public function renderForm() {
        $this->template->links = $this->createBackFullUrl('User:Documents', 'list', ['folderId' => $this->httpGet('folderId')]);
    }

    protected function createComponentCreateDocumentForm(HttpRequest $request) {
        $classesDb = $this->documentManager->getDocumentClassesForDocumentCreateForUser($this->getUserId());

        $classes = [];
        foreach($classesDb as $id => $text) {
            $classes[] = [
                'value' => $id,
                'text' => $text
            ];
        }

        $form = new FormBuilder2($request);

        $form->setAction($this->createURL('form', ['folderId' => $request->query['folderId']]));

        $form->addTextInput('title', 'Title:')
            ->setRequired();

        $form->addTextArea('description', 'Description:')
            ->setRequired();

        $form->addSelect('class', 'Class:')
            ->addRawOptions($classes)
            ->setRequired();

        $this->addCustomMetadataFormControls($form, $request->query['folderId']);

        $form->addSubmit('Create');

        return $form;
    }
}

?>