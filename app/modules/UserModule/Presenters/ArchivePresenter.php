<?php

namespace App\Modules\UserModule;

use App\Components\ArchiveFoldersSidebar\ArchiveFoldersSidebar;
use App\Components\DocumentsGrid\DocumentsGrid;
use App\Constants\Container\GridNames;
use App\Constants\SessionNames;
use App\Core\Http\HttpRequest;

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

        $folder = $this->archiveManager->getArchiveFolderById($this->currentFolderId);
        $this->saveToPresenterCache('folderTitle', $folder->title);
    }

    public function renderList() {
        $this->template->sidebar = $this->loadFromPresenterCache('sidebar');
        $this->template->links = [
        ];
        $this->template->folder_title = $this->loadFromPresenterCache('folderTitle');
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
            $this->documentBulkActionAuthorizator,
            $this->groupStandardOperationsAuthorizator,
            $this->enumManager,
            $this->gridManager,
            $this->processFactory,
            $this->archiveManager,
            $this->fileStorageManager
        );

        if(!$this->httpRequest->isAjax) {
            $documentsGrid->setCurrentArchiveFolder($this->currentFolderId);
        }
        $documentsGrid->showCustomMetadata();
        $documentsGrid->useCheckboxes($this);
        $documentsGrid->setGridName(GridNames::DOCUMENTS_GRID);

        return $documentsGrid;
    }
}

?>