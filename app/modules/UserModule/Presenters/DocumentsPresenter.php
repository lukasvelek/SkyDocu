<?php

namespace App\Modules\UserModule;

use App\Components\DocumentsGrid\DocumentsGrid;
use App\Components\DocumetnShareForm\DocumentShareForm;
use App\Components\FoldersSidebar\FoldersSidebar;
use App\Constants\Container\CustomMetadataTypes;
use App\Constants\Container\DocumentStatus;
use App\Core\DB\DatabaseRow;
use App\Core\Http\FormRequest;
use App\Core\Http\HttpRequest;
use App\Enums\AEnumForMetadata;
use App\Exceptions\AException;
use App\Exceptions\RequiredAttributeIsNotSetException;
use App\Helpers\DateTimeFormatHelper;
use App\UI\LinkBuilder;

class DocumentsPresenter extends AUserPresenter {
    private ?string $currentFolderId;

    public function __construct() {
        parent::__construct('DocumentsPresenter', 'Documents');
    }

    public function startup() {
        parent::startup();

        $this->currentFolderId = $this->httpSessionGet('current_document_folder_id');
    }

    protected function createComponentFoldersSidebar(HttpRequest $request) {
        $sidebar = new FoldersSidebar($request, $this->folderManager, 'list');

        return $sidebar;
    }
    
    public function handleList() {
        $folderId = $this->httpRequest->query('folderId');

        if($folderId !== null) {
            $this->currentFolderId = $folderId;
        } else if($folderId === null && $this->currentFolderId === null && $this->httpSessionGet('current_document_folder_id') === null) {
            $folder = $this->folderManager->getDefaultFolder();
            $this->redirect($this->createURL('switchFolder', ['folderId' => $folder->folderId]));
        }

        if($this->currentFolderId !== null) {
            $folder = $this->folderManager->getFolderById($this->currentFolderId);

            $this->saveToPresenterCache('folderTitle', $folder->title);
        }
    }

    public function renderList() {
        $this->template->sidebar = $this->loadFromPresenterCache('sidebar');
        $this->template->links = [
            LinkBuilder::createSimpleLink('New document', $this->createFullURL('User:CreateDocument', 'form', ['folderId' => $this->currentFolderId]), 'link')
        ];
        $this->template->folder_title = $this->loadFromPresenterCache('folderTitle');
    }

    protected function createComponentDocumentsGrid(HttpRequest $request) {
        $documentsGrid = new DocumentsGrid(
            $this->componentFactory->getGridBuilder($this->containerId),
            $this->app,
            $this->documentManager,
            $this->documentBulkActionAuthorizator,
            $this->groupStandardOperationsAuthorizator,
            $this->enumManager,
            $this->gridManager,
            $this->processFactory
        );

        $documentsGrid->setCurrentFolder($this->currentFolderId);
        $documentsGrid->showCustomMetadata();
        $documentsGrid->useCheckboxes($this);
        $documentsGrid->setCacheFactory($this->cacheFactory);

        return $documentsGrid;
    }

    public function handleSwitchFolder() {
        $folderId = $this->httpRequest->query('folderId');
        if($folderId === null) {
            throw new RequiredAttributeIsNotSetException('folderId');
        }
        $this->httpSessionSet('current_document_folder_id', $folderId);
        $this->redirect($this->createURL('list', ['folderId' => $folderId]));
    }

    public function handleInfo() {
        $documentId = $this->httpRequest->query('documentId');
        if($documentId === null) {
            throw new RequiredAttributeIsNotSetException('documentId');
        }
        
        try {
            $document = $this->documentManager->getDocumentById($documentId);
        } catch(AException $e) {
            $this->flashMessage('Document not found. Reason: ' . $e->getMessage(), 'error', 10);
            $this->redirect($this->createURL('list'));
        }

        // BASIC INFORMATION
        $basicInformationCode = '';
        $createRow = function(string $title, mixed $data) use (&$basicInformationCode) {
            $basicInformationCode .= '<p class="changelog-item"><b>' . $title . ': </b>' . $data . '</p>';
        };

        $createRow('Title', $document->title);
        $createRow('Status', DocumentStatus::toString($document->status));

        $author = '-';
        try {
            $author = $this->app->userManager->getUserById($document->authorUserId)->getUsername();
        } catch(AException) {}

        $createRow('Author', $author);
        $createRow('Description', $document->description);

        $class = '-';
        try {
            $classes = $this->documentManager->getDocumentClassesForDocumentCreateForUser($this->getUserId());

            $class = $classes[$document->classId];
        } catch(AException) {}

        $createRow('Class', $class);

        $folder = '-';
        try {
            $folder = $this->folderManager->getFolderById($document->folderId)->title;
        } catch(AException) {}

        $createRow('Folder', $folder);
        $createRow('Date created', DateTimeFormatHelper::formatDateToUserFriendly($document->dateCreated));
        $createRow('Date modified', ($document->dateModified !== null) ? DateTimeFormatHelper::formatDateToUserFriendly($document->dateModified) : '-');

        $this->saveToPresenterCache('documentBasicInformation', $basicInformationCode);

        // CUSTOM METADATA
        $customMetadataCode = '';
        $createRow = function(string $title, mixed $data) use (&$customMetadataCode) {
            $customMetadataCode .= '<p class="changelog-item"><b>' . $title . ': </b>' . $data . '</p>';
        };

        $docuRow = $this->documentRepository->getDocumentById($documentId);
        $docuRow = DatabaseRow::createFromDbRow($docuRow);
        $systemMetadata = [];
        foreach($docuRow->getKeys() as $key) {
            $systemMetadata[] = $key;
        }

        $metadataInFolder = $this->metadataManager->getMetadataForFolder($document->folderId);

        foreach($document->getKeys() as $metadataTitle) {
            if(!in_array($metadataTitle, $systemMetadata)) {
                if(array_key_exists($metadataTitle, $metadataInFolder)) {
                    $metaRow = $metadataInFolder[$metadataTitle];

                    $data = $document->$metadataTitle;

                    if($data === null) {
                        $data = '-';
                    } else {
                        switch($metaRow->type) {
                            case CustomMetadataTypes::BOOL:
                                if($data === true) {
                                    $data = '<span style="color: green">&check;</span>';
                                } else {
                                    $data = '<span style="color: red">&cross;</span>';
                                }

                                break;

                            case CustomMetadataTypes::DATE:
                            case CustomMetadataTypes::DATETIME:
                                $data = DateTimeFormatHelper::formatDateToUserFriendly($data);

                                break;

                            case CustomMetadataTypes::ENUM:
                                $enumValues = $this->metadataManager->getMetadataEnumValues($metaRow->metadataId);

                                if(array_key_exists($data, $enumValues)) {
                                    $data = $enumValues[$data];
                                } else {
                                    $data = '-';
                                }

                                break;

                            default:
                                if($metaRow->type >= 100) { // system enums
                                    $metaValues = $this->enumManager->getMetadataEnumValuesByMetadataType($metaRow);

                                    if($metaValues->keyExists($data)) {
                                        $data = $metaValues->get($data)[AEnumForMetadata::TITLE];
                                    } else {
                                        $data = '-';
                                    }
                                }
                                break;
                        }
                    }

                    $createRow($metaRow->guiTitle, $data);
                }
            }
        }

        $this->saveToPresenterCache('customMetadataCode', $customMetadataCode);
    }

    public function renderInfo() {
        $this->template->document_basic_information = $this->loadFromPresenterCache('documentBasicInformation');
        $this->template->document_custom_metadata = $this->loadFromPresenterCache('customMetadataCode');

        $this->template->links = $this->createBackUrl('list');
    }

    public function handleShareForm(?FormRequest $fr = null) {
        if($fr !== null) {
            $data = $fr->getData();

            try {
                $this->documentRepository->beginTransaction(__METHOD__);

                foreach($this->httpRequest->query('documentId') as $documentId) {
                    $this->documentManager->shareDocument($documentId, $this->getUserId(), $data['user']);
                }

                $this->documentRepository->commit($this->getUserId(), __METHOD__);

                $this->flashMessage(sprintf('Successfully shared %d %s.', count($this->httpRequest->query('documentId')), (count($this->httpRequest->query('documentId')) > 1 ? 'documents' : 'document')), 'success');
            } catch(AException $e) {
                $this->documentRepository->rollback(__METHOD__);
                
                $this->flashMessage('Could not share document' . (count($this->httpRequest->query('documentId')) > 1 ? 's' : '') . '. Reason: ' . $e->getMessage(), 'error');
            }

            $this->redirect($this->createURL('list', ['folderId' => $this->httpRequest->query('folderId')]));
        }
    }

    public function renderShareForm() {
        $this->template->links = $this->createBackUrl('list', ['folderId' => $this->httpRequest->query('backFolderId')]);
    }

    protected function createComponentShareDocumentForm(HttpRequest $request) {
        $form = new DocumentShareForm($request, $this->app->userRepository, $this->documentManager);

        if(!$request->isAjax) {
            $form->setAction($this->createURL('shareForm', ['folderId' => $request->query('backFolderId')]));
            $form->setDocumentIds($request->query('documentId'));
        }

        return $form;
    }

    public function renderListShared() {}

    protected function createComponentSharedDocumentsGrid(HttpRequest $request) {
        $documentsGrid = new DocumentsGrid(
            $this->componentFactory->getGridBuilder($this->containerId),
            $this->app,
            $this->documentManager,
            $this->documentBulkActionAuthorizator,
            $this->groupStandardOperationsAuthorizator,
            $this->enumManager,
            $this->gridManager,
            $this->processFactory
        );

        $documentsGrid->setShowShared();
        $documentsGrid->useCheckboxes($this);
        $documentsGrid->setCacheFactory($this->cacheFactory);

        return $documentsGrid;
    }
}

?>