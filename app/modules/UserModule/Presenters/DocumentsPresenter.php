<?php

namespace App\Modules\UserModule;

use App\Components\DocumentsGrid\DocumentsGrid;
use App\Components\DocumetnShareForm\DocumentShareForm;
use App\Components\FoldersSidebar\FoldersSidebar;
use App\Constants\Container\CustomMetadataTypes;
use App\Constants\Container\DocumentStatus;
use App\Constants\Container\GridNames;
use App\Constants\SessionNames;
use App\Core\Caching\CacheNames;
use App\Core\DB\DatabaseRow;
use App\Core\FileUploadManager;
use App\Core\Http\Ajax\Requests\PostAjaxRequest;
use App\Core\Http\FormRequest;
use App\Core\Http\HttpRequest;
use App\Enums\AEnumForMetadata;
use App\Exceptions\AException;
use App\Exceptions\GeneralException;
use App\Exceptions\RequiredAttributeIsNotSetException;
use App\Helpers\DateTimeFormatHelper;
use App\Helpers\LinkHelper;
use App\Helpers\UnitConversionHelper;
use App\UI\GridBuilder2\CheckboxLink;
use App\UI\HTML\HTML;
use App\UI\LinkBuilder;

class DocumentsPresenter extends AUserPresenter {
    private ?string $currentFolderId;

    public function __construct() {
        parent::__construct('DocumentsPresenter', 'Documents');
    }

    public function startup() {
        parent::startup();

        $this->currentFolderId = $this->httpSessionGet(SessionNames::CURRENT_DOCUMENT_FOLDER_ID);
    }

    protected function createComponentFoldersSidebar(HttpRequest $request) {
        $sidebar = new FoldersSidebar($request, $this->folderManager, 'list');

        return $sidebar;
    }
    
    public function handleList() {
        $folderId = $this->httpRequest->get('folderId');

        if($folderId !== null) {
            $this->currentFolderId = $folderId;
        } else if($this->httpRequest->post('folderId') !== null) {
            $this->currentFolderId = $this->httpRequest->post('folderId');
        } else {
            if(str_contains($this->httpRequest->get('do'), 'getSkeleton')) {
                $this->currentFolderId = $this->folderManager->getDefaultFolder()->folderId;
            } else {
                $this->redirect($this->createURL('list', ['folderId' => $this->folderManager->getDefaultFolder()->folderId]));
            }
        }
    }

    public function renderList() {
        $folder = '';
        if($this->currentFolderId !== null) {
            $folder = $this->folderManager->getFolderById($this->currentFolderId)->title;
        }

        $this->template->links = [
            LinkBuilder::createSimpleLink('New document', $this->createFullURL('User:CreateDocument', 'form', ['folderId' => $this->currentFolderId]), 'link')
        ];
        $this->template->folder_title = $folder;

        $this->addScript('
            function processBulkAction_MoveToFolder(_ids) {
                const url = "' . $this->createURLString('moveToFolderForm', ['currentFolderId' => $this->currentFolderId]) . '";

                post(url, _ids);
            }

            function processBulkAction_MoveToArchive(_ids) {
                const url = "' . $this->createURLString('moveToArchiveForm', ['folderId' => $this->currentFolderId]) . '";

                post(url, _ids);
            }
        ');
    }

    protected function createComponentDocumentsGrid() {
        $documentsGrid = new DocumentsGrid(
            $this->componentFactory->getGridBuilder($this->containerId),
            $this->app,
            $this->documentManager,
            $this->groupStandardOperationsAuthorizator,
            $this->enumManager,
            $this->gridManager,
            $this->archiveManager
        );

        if(!$this->httpRequest->isAjax || str_contains($this->httpRequest->get('do'), 'getSkeleton')) {
            $documentsGrid->setCurrentFolder($this->currentFolderId);
        }
        $documentsGrid->showCustomMetadata();
        $documentsGrid->useCheckboxes($this);
        $documentsGrid->setGridName(GridNames::DOCUMENTS_GRID);

        $documentsGrid->addCheckboxLinkCallback(
            (new CheckboxLink('moveToFolder'))
                ->setCheckCallback(function(string $primaryKey) {
                    try {
                        $document = $this->documentManager->getDocumentById($primaryKey);

                        if(!in_array($document->status, [DocumentStatus::NEW])) {
                            return false;
                        }

                        return true;
                    } catch(AException $e) {
                        return false;
                    }
                })
                ->setLinkCallback(function(array $primaryKeys) {
                    $data = [
                        'ids' => $primaryKeys
                    ];

                    return LinkBuilder::createJSOnclickLink('Move to folder',
                        'processBulkAction_MoveToFolder(' . htmlspecialchars(json_encode($data)) . ')',
                        'link'
                    );
                })
        );

        $documentsGrid->addCheckboxLinkCallback(
            (new CheckboxLink('moveToArchive'))
                ->setCheckCallback(function(string $primaryKey) {
                    try {
                        $document = $this->documentManager->getDocumentById($primaryKey);

                        if(!in_array($document->status, [DocumentStatus::NEW])) {
                            return false;
                        }

                        return true;
                    } catch(AException $e) {
                        return false;
                    }
                })
                ->setLinkCallback(function(array $primaryKeys) {
                    $data = [
                        'ids' => $primaryKeys
                    ];

                    return LinkBuilder::createJSOnclickLink('Move to archive', 
                        'processBulkAction_MoveToArchive(' . htmlspecialchars(json_encode($data)) . ')',
                        'link'
                    );
                })
        );

        return $documentsGrid;
    }

    public function handleSwitchFolder() {
        $folderId = $this->httpRequest->get('folderId');
        if($folderId === null) {
            throw new RequiredAttributeIsNotSetException('folderId');
        }
        $this->httpSessionSet('current_document_folder_id', $folderId);
        $this->redirect($this->createURL('list', ['folderId' => $folderId]));
    }

    public function handleInfo() {
        $documentId = $this->httpRequest->get('documentId');
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
            $author = $this->app->userManager->getUserById($document->authorUserId)->getFullname();
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
        $createRow('Date created', DateTimeFormatHelper::formatDateToUserFriendly($document->dateCreated, $this->app->currentUser->getDatetimeFormat()));
        $createRow('Date modified', ($document->dateModified !== null) ? DateTimeFormatHelper::formatDateToUserFriendly($document->dateModified, $this->app->currentUser->getDatetimeFormat()) : '-');

        // FILE ATTACHMENT
        $fileId = $this->documentRepository->getFileIdForDocumentId($document->documentId);
        if($fileId !== null) {
            $url = $this->app->fileStorageManager->generateDownloadLinkForFileInDocument($fileId, $this->containerId);
            $file = $this->app->fileStorageManager->getFileById($fileId, $this->containerId);
            $fileSize = UnitConversionHelper::convertBytesToUserFriendly($file->filesize);

            $el = HTML::el('a')
                ->text('Download file (' . $fileSize . ')')
                ->class('changelog-link')
                ->target('_blank')
                ->href($url);

            $createRow('File', $el->toString());
        }
        // END OF FILE ATTACHMENT

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
                                $data = DateTimeFormatHelper::formatDateToUserFriendly($data, $this->app->currentUser->getDateFormat());
                                
                                break;

                            case CustomMetadataTypes::DATETIME:
                                $data = DateTimeFormatHelper::formatDateToUserFriendly($data, $this->app->currentUser->getDatetimeFormat());

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

        $links = [
            $this->createBackUrl('list', ['folderId' => $document->folderId])
        ];

        $this->saveToPresenterCache('links', LinkHelper::createLinksFromArray($links));
    }

    public function renderInfo() {
        $this->template->document_basic_information = $this->loadFromPresenterCache('documentBasicInformation');
        $this->template->document_custom_metadata = $this->loadFromPresenterCache('customMetadataCode');

        $this->template->links = $this->loadFromPresenterCache('links');
    }

    public function handleShareForm(?FormRequest $fr = null) {
        if($fr !== null) {
            $data = $fr->getData();

            try {
                $this->documentRepository->beginTransaction(__METHOD__);

                foreach($this->httpRequest->get('documentId') as $documentId) {
                    $this->documentManager->shareDocument($documentId, $this->getUserId(), $data['user']);
                }

                $this->documentRepository->commit($this->getUserId(), __METHOD__);

                $this->flashMessage(sprintf('Successfully shared %d %s.', count($this->httpRequest->get('documentId')), (count($this->httpRequest->get('documentId')) > 1 ? 'documents' : 'document')), 'success');
            } catch(AException $e) {
                $this->documentRepository->rollback(__METHOD__);
                
                $this->flashMessage('Could not share document' . (count($this->httpRequest->get('documentId')) > 1 ? 's' : '') . '. Reason: ' . $e->getMessage(), 'error');
            }

            $this->redirect($this->createURL('list', ['folderId' => $this->httpRequest->get('folderId')]));
        }
    }

    public function renderShareForm() {
        $this->template->links = $this->createBackUrl('list', ['folderId' => $this->httpRequest->get('backFolderId')]);
    }

    protected function createComponentShareDocumentForm(HttpRequest $request) {
        $form = new DocumentShareForm($request, $this->app->userRepository, $this->documentManager);

        if(!$request->isAjax) {
            $form->setAction($this->createURL('shareForm', ['folderId' => $request->get('backFolderId')]));
            $form->setDocumentIds($request->get('documentId'));
        }

        return $form;
    }

    public function renderListShared() {}

    protected function createComponentSharedDocumentsGrid(HttpRequest $request) {
        $documentsGrid = new DocumentsGrid(
            $this->componentFactory->getGridBuilder($this->containerId),
            $this->app,
            $this->documentManager,
            $this->groupStandardOperationsAuthorizator,
            $this->enumManager,
            $this->gridManager,
            $this->archiveManager
        );

        $documentsGrid->setShowShared();
        $documentsGrid->useCheckboxes($this);

        return $documentsGrid;
    }

    public function handleMoveToFolderForm() {
        if($this->httpRequest->get('ids') === null) {
            $this->flashMessage('No documents were selected.', 'error', 10);
            $this->redirect($this->createURL('list', ['folderId' => $this->httpRequest->get('currentFolderId')]));
        }
    }

    public function renderMoveToFolderForm() {
        $links = [
            $this->createBackUrl('list', ['folderId' => $this->httpRequest->get('currentFolderId')])
        ];

        $this->template->links = LinkHelper::createLinksFromArray($links);
    }

    protected function createComponentMoveDocumentToFolderForm() {
        $folders = [];
        $foldersDb = $this->folderManager->getVisibleFoldersForUser($this->getUserId());

        foreach($foldersDb as $folder) {
            if($this->folderManager->hasFolderCustomMetadata($folder->folderId, $this->getUserId())) {
                continue;
            }

            if($folder->folderId == $this->httpRequest->get('currentFolderId')) continue;

            $folders[] = [
                'value' => $folder->folderId,
                'text' => $folder->title
            ];
        }

        if(empty($folders)) {
            $this->flashMessage('No folders are available.', 'error', 10);
            $this->redirect($this->createURL('list', ['folderId' => $this->httpRequest->get('currentFolderId')]));
        }

        $form = $this->componentFactory->getFormBuilder();

        $form->setAction($this->createURL('moveToFolderFormSubmit', [
            'currentFolderId' => $this->httpRequest->get('currentFolderId'),
            'ids' => $this->httpRequest->get('ids')
        ]));

        $form->addSelect('folder', 'Folder:')
            ->setRequired()
            ->addRawOptions($folders);

        $form->addHiddenInput('ids')
            ->setValue($this->httpRequest->get('ids'));

        $form->addSubmit('Move to folder');

        return $form;
    }

    public function handleMoveToFolderFormSubmit(FormRequest $fr) {
        $documentIds = explode(',', $this->httpRequest->get('ids'));

        try {
            $this->folderRepository->beginTransaction(__METHOD__);

            foreach($documentIds as $documentId) {
                $this->documentManager->updateDocument($documentId, ['folderId' => $fr->folder]);
            }

            $this->folderRepository->commit($this->getUserId(), __METHOD__);

            $this->flashMessage('Documents moved to selected folder successfully.', 'success');
        } catch(AException $e) {
            $this->folderRepository->rollback(__METHOD__);

            $this->flashMessage('Could not move documents to selected folder. Reason: ' . $e->getMessage(), 'error', 10);
        }

        $this->redirect($this->createURL('list', ['folderId' => $this->httpRequest->get('currentFolderId')]));
    }

    public function renderMoveToArchiveForm() {
        $links = [
            $this->createBackUrl('list', ['folderId' => $this->httpRequest->get('folderId')])
        ];

        $this->template->links = $links;
    }

    protected function createComponentMoveDocumentToArchiveForm() {
        $qb = $this->archiveManager->composeQueryForAvailableArchiveFolders();
        $qb->execute();

        $archive = [];
        while($row = $qb->fetchAssoc()) {
            $archive[] = [
                'value' => $row['folderId'],
                'text' => $row['title']
            ];
        }

        $form = $this->componentFactory->getFormBuilder();

        $form->setAction($this->createURL('moveToArchiveFormSubmit', ['folderId' => $this->httpRequest->get('folderId')]));

        $form->addSelect('archiveFolder', 'Archive folder:')
            ->setRequired()
            ->addRawOptions($archive);

        $form->addHiddenInput('ids')
            ->setValue($this->httpRequest->post('ids'));

        $form->addSubmit('Move to archive');

        return $form;
    }

    public function handleMoveToArchiveFormSubmit(FormRequest $fr) {
        $this->mandatoryUrlParams(['folderId'], $this->createURL('list'));

        $folderId = $this->httpRequest->get('folderId');
        $documentIds = explode(',', $this->httpRequest->get('ids'));

        try {
            $this->archiveRepository->beginTransaction(__METHOD__);

            $this->archiveManager->bulkInsertDocumentsToArchiveFolder($fr->archiveFolder, $documentIds);

            $this->documentManager->bulkUpdateDocuments($documentIds, [
                'status' => DocumentStatus::ARCHIVED
            ]);

            $this->archiveRepository->commit($this->getUserId(), __METHOD__);

            $this->flashMessage('Successfully moved files to archive.', 'success');
        } catch(AException $e) {
            $this->archiveRepository->rollback(__METHOD__);

            $this->flashMessage('Could not move files to archive. Reason: ' . $e->getMessage(), 'error', 10);
        }

        $this->redirect($this->createURL('list', ['folderId' => $folderId]));
    }
}

?>