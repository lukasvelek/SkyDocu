<?php

namespace App\Modules\UserModule;

use App\Components\ArchiveFoldersSidebar\ArchiveFoldersSidebar;
use App\Components\DocumentsGrid\DocumentsGrid;
use App\Constants\Container\DocumentStatus;
use App\Constants\Container\GridNames;
use App\Constants\SessionNames;
use App\Core\Http\HttpRequest;
use App\Exceptions\AException;
use App\UI\GridBuilder2\CheckboxLink;
use App\UI\LinkBuilder;

class ArchivePresenter extends AUserPresenter {
    private ?string $currentFolderId;

    public function __construct() {
        parent::__construct('ArchivePresenter', 'Archive');
    }

    public function startup() {
        parent::startup();

        $this->currentFolderId = $this->httpSessionGet(SessionNames::CURRENT_ARCHIVE_FOLDER_ID);
    }

    public function handleList() {
        if($this->httpRequest->get('folderId') !== null) {
            $this->currentFolderId = $this->httpRequest->get('folderId');
        } else {
            $this->redirect($this->createURL('list', ['folderId' => $this->archiveManager->getDefaultFolder()->folderId]));
        }
    }

    public function renderList() {
        $folder = $this->archiveManager->getArchiveFolderById($this->currentFolderId);

        $this->template->links = [];
        $this->template->folder_title = $folder->title;

        $this->addScript('
            function processBulkAction(data) {
                post(data.url, {"ids": data.ids});
            }
        ');
    }

    protected function createComponentFoldersSidebar(HttpRequest $request) {
        $sidebar = new ArchiveFoldersSidebar($request, $this->archiveManager, 'list');

        return $sidebar;
    }

    protected function createComponentDocumentsGrid(HttpRequest $request) {
        $documentsGrid = new DocumentsGrid(
            $this->componentFactory->getGridBuilder($this->containerId),
            $this->app,
            $this->documentManager,
            $this->groupStandardOperationsAuthorizator,
            $this->enumManager,
            $this->gridManager,
            $this->archiveManager
        );

        if(!$this->httpRequest->isAjax) {
            $documentsGrid->setCurrentArchiveFolder($this->currentFolderId);
        }
        $documentsGrid->showCustomMetadata();
        $documentsGrid->useCheckboxes($this);
        $documentsGrid->setGridName(GridNames::DOCUMENTS_GRID);

        $documentsGrid->addCheckboxLinkCallback(
            (new CheckboxLink('moveFromArchive'))
                ->setCheckCallback(function(string $primaryKey) {
                    try {
                        $document = $this->documentManager->getDocumentById($primaryKey);

                        if(!in_array($document->status, [DocumentStatus::ARCHIVED])) {
                            return false;
                        }

                        return true;
                    } catch(AException $e) {
                        return false;
                    }
                })
                ->setLinkCallback(function(array $primaryKeys) {
                    $data = [
                        'ids' => $primaryKeys,
                        'url' => $this->createURLString('moveFromArchive', ['folderId' => $this->currentFolderId])
                    ];

                    return LinkBuilder::createJSOnclickLink('Move from archive',
                        'processBulkAction(' . htmlspecialchars(json_encode($data)) . ')',
                        'link'
                    );
                })
        );

        return $documentsGrid;
    }

    public function handleMoveFromArchive() {
        $documentIds = explode(',', $this->httpRequest->post('ids'));
        $folderId = $this->httpRequest->get('folderId');

        try {
            $this->documentRepository->beginTransaction(__METHOD__);

            $this->archiveManager->bulkRemoveDocumentsFromArchiveFolder($documentIds);

            $this->documentManager->bulkUpdateDocuments($documentIds, [
                'status' => DocumentStatus::NEW
            ]);

            $this->documentRepository->commit($this->getUserId(), __METHOD__);

            $this->flashMessage('Successfully moved documents from archive.', 'success');
        } catch(AException $e) {
            $this->documentRepository->rollback(__METHOD__);

            $this->flashMessage('Could not move documents from archive. Reason: ' . $e->getMessage(), 'error', 10);
        }

        $this->redirect($this->createURL('list', ['folderId' => $folderId]));
    }
}

?>