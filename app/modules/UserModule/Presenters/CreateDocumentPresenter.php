<?php

namespace App\Modules\UserModule;

use App\Constants\Container\CustomMetadataTypes;
use App\Constants\Container\DocumentStatus;
use App\Core\FileUploadManager;
use App\Core\Http\FormRequest;
use App\Core\Http\HttpRequest;
use App\Exceptions\AException;
use App\Exceptions\RequiredAttributeIsNotSetException;
use App\UI\FormBuilder2\FormBuilder2;

class CreateDocumentPresenter extends AUserPresenter {
    public function __construct() {
        parent::__construct('CreateDocumentPresenter', 'Create document');
    }

    private function addCustomMetadataFormControls(FormBuilder2 $form, string $folderId) {
        $customMetadatas = $this->documentManager->getCustomMetadataForFolder($folderId);

        foreach($customMetadatas as $metadataId => $metadata) {
            switch($metadata->type) {
                case CustomMetadataTypes::BOOL:
                    $element = $form->addCheckboxInput($metadata->title, $metadata->guiTitle . ':');
                    break;

                case CustomMetadataTypes::DATETIME:
                    $element = $form->addDateTimeInput($metadata->title, $metadata->guiTitle . ':')
                        ->setRequired();
                    break;

                case CustomMetadataTypes::DATE:
                    $element = $form->addDateInput($metadata->title, $metadata->guiTitle . ':')
                        ->setRequired();
                    break;

                case CustomMetadataTypes::ENUM:
                    $selectValuesDb = $this->documentManager->getMetadataValues($metadataId);

                    $values = [];
                    foreach($selectValuesDb as $key => $title) {
                        $values[] = [
                            'value' => $key,
                            'text' => $title
                        ];
                    }
                    $element = $form->addSelect($metadata->title, $metadata->guiTitle . ':')
                        ->addRawOptions($values);

                    break;

                case CustomMetadataTypes::NUMBER:
                    $element = $form->addNumberInput($metadata->title, $metadata->guiTitle . ':');
                    break;

                case CustomMetadataTypes::TEXT:
                    $element = $form->addTextInput($metadata->title, $metadata->guiTitle . ':');
                    break;

                default:
                    if($metadata->type >= 100) {
                        $element = $form->addSelect($metadata->title, $metadata->guiTitle . ':');

                        $values = $this->enumManager->getMetadataEnumValuesByMetadataTypeForSelect($metadata);

                        if($values === null) {
                            break;
                        }

                        $element->addRawOptions($values);
                    }

                    break;
            }

            if($metadata->isRequired) {
                $element->setRequired();
            }
        }

        return $form;
    }

    public function handleForm(?FormRequest $fr = null) {
        if($fr !== null) {
            $folderId = $this->httpRequest->get('folderId');
            if($folderId === null) {
                throw new RequiredAttributeIsNotSetException('folderId');
            }

            $customMetadatas = $this->documentManager->getCustomMetadataForFolder($folderId);

            $metadataValues = [
                'title' => $fr->title,
                'classId' => $fr->class,
                'authorUserId' => $this->getUserId(),
                'status' => DocumentStatus::NEW,
                'folderId' => $folderId
            ];

            if($fr->isset('description')) {
                $metadataValues['description'] = $fr->description;
            }

            $customMetadataValues = [];
            foreach($customMetadatas as $metadataId => $metadata) {
                if($fr->isset($metadata->title)) {
                    if($fr->{$metadata->title} != 'null') {
                        $customMetadataValues[$metadataId] = $fr->{$metadata->title};
                    }
                }
            }

            try {
                $this->documentClassRepository->beginTransaction(__METHOD__);

                // create document
                $documentId = $this->documentManager->createNewDocument($metadataValues, $customMetadataValues);

                // upload file
                $fum = new FileUploadManager();
                $fileData = $fum->uploadFile($_FILES['file'], $this->getUserId(), $this->containerId, ['documentId' => $documentId]);

                // insert file to storage
                try {
                    $this->app->fileStorageRepository->beginTransaction(__METHOD__);

                    $fileId = $this->app->fileStorageManager->createNewFile(
                        $this->getUserId(),
                        $fileData[FileUploadManager::FILE_FILENAME],
                        $fileData[FileUploadManager::FILE_FILEPATH],
                        $fileData[FileUploadManager::FILE_FILESIZE],
                        $this->containerId
                    );
                    
                    $this->app->fileStorageRepository->commit($this->getUserId(), __METHOD__);
                } catch(AException $e) {
                    $this->app->fileStorageRepository->rollback(__METHOD__);

                    throw $e;
                }

                // create file-document relation
                $this->fileStorageManager->createNewFileDocumentRelation($documentId, $fileId);

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
        $this->template->links = $this->createBackFullUrl('User:Documents', 'list', ['folderId' => $this->httpRequest->get('folderId')]);
    }

    protected function createComponentCreateDocumentForm(HttpRequest $request) {
        $folderId = $request->get('folderId');

        $classesDb = $this->documentManager->getDocumentClassesForDocumentCreateForUser($this->getUserId());

        $classes = [];
        foreach($classesDb as $id => $text) {
            $classes[] = [
                'value' => $id,
                'text' => $text
            ];
        }

        $form = $this->componentFactory->getFormBuilder();

        $form->setAction($this->createURL('form', ['folderId' => $folderId]));

        $form->addTextInput('title', 'Title:')
            ->setRequired();

        $form->addTextArea('description', 'Description:')
            ->setRequired();

        $form->addSelect('class', 'Class:')
            ->addRawOptions($classes)
            ->setRequired();

        $form->addFileInput('file', 'File:')
            ->setRequired();

        $this->addCustomMetadataFormControls($form, $folderId);

        $form->addSubmit('Create');

        return $form;
    }
}

?>