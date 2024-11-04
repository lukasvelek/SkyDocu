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
            }

            if($this->currentFolderId === null && $vf->title == 'Default') {
                $this->redirect($this->createURL('switchFolder', ['folderId' => $vf->folderId]));
            }
            
            $foldersSidebar->addLink($vf->title, $this->createURL('switchFolder', ['folderId' => $vf->folderId]), $active);
        }

        $this->saveToPresenterCache('sidebar', $foldersSidebar->render());
    }

    public function renderList() {
        $this->template->sidebar = $this->loadFromPresenterCache('sidebar');
        $this->template->links = [
            LinkBuilder::createSimpleLink('New document', $this->createFullURL('User:CreateDocument', 'form', ['folderId' => $this->httpGet('folderId')]), 'link')
        ];
    }

    protected function createComponentDocumentsGrid(HttpRequest $request) {
        $documentsGrid = new DocumentsGrid($this->getGridBuilder(), $this->app, $this->documentManager);

        $documentsGrid->showCustomMetadata();

        return $documentsGrid;
    }

    public function handleSwitchFolder() {
        $folderId = $this->httpGet('folderId', true);
        $this->httpSessionSet('current_document_folder_id', $folderId);
        $this->redirect($this->createURL('list', ['folderId' => $folderId]));
    }
}

?>