<?php

namespace App\Modules\UserModule;

use App\Components\DocumentsGrid\DocumentsGrid;
use App\Components\Sidebar\Sidebar;
use App\Core\Http\HttpRequest;
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

    private function getDefaultFolder() {
        $visibleFolders = $this->folderManager->getVisibleFoldersForUser($this->getUserId());

        foreach($visibleFolders as $vf) {
            if($this->currentFolderId === null && $vf->title == 'Default') {
                $this->redirect($this->createURL('switchFolder', ['folderId' => $vf->folderId]));
            }
        }
    }
    
    public function handleList() {
        $folderId = $this->httpGet('folderId');

        if($folderId !== null) {
            $this->currentFolderId = $folderId;
        }

        $foldersSidebar = new Sidebar();
        
        $visibleFolders = $this->folderManager->getVisibleFoldersForUser($this->getUserId());

        foreach($visibleFolders as $vf) {
            $active = false;

            if($this->currentFolderId == $vf->folderId) {
                $active = true;
                $this->saveToPresenterCache('folderTitle', $vf->title);
            }

            if($this->currentFolderId === null && $vf->title == 'Default') {
                $this->redirect($this->createURL('switchFolder', ['folderId' => $vf->folderId]));
            }
            
            $foldersSidebar->addLink($vf->title, $this->createURL('switchFolder', ['folderId' => $vf->folderId]), $active);
            //$foldersSidebar->addJSLink($vf->title, 'documentsGrid_gridRefresh(0, \'' . $vf->folderId . '\')', $active);
        }

        $this->saveToPresenterCache('sidebar', $foldersSidebar->render());
    }

    public function renderList() {
        $this->template->sidebar = $this->loadFromPresenterCache('sidebar');
        $this->template->links = [
            LinkBuilder::createSimpleLink('New document', $this->createFullURL('User:CreateDocument', 'form', ['folderId' => $this->currentFolderId]), 'link')
        ];
        $this->template->folder_title = $this->loadFromPresenterCache('folderTitle');
    }

    protected function createComponentDocumentsGrid(HttpRequest $request) {
        $documentsGrid = new DocumentsGrid($this->componentFactory->getGridBuilder(), $this->app, $this->documentManager, $this->documentBulkActionAuthorizator, $this->groupStandardOperationsAuthorizator);
        $documentsGrid->setGridName('documentsGrid');

        $documentsGrid->showCustomMetadata();
        $documentsGrid->setCurrentFolder($this->currentFolderId);
        $documentsGrid->useCheckboxes($this);

        return $documentsGrid;
    }

    public function handleSwitchFolder() {
        $folderId = $this->httpGet('folderId', true);
        $this->httpSessionSet('current_document_folder_id', $folderId);
        $this->redirect($this->createURL('list', ['folderId' => $folderId]));
    }
}

?>