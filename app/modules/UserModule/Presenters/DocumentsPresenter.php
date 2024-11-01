<?php

namespace App\Modules\UserModule;

use App\Components\Sidebar\Sidebar;
use App\Core\Http\HttpRequest;

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
    }

    protected function createComponentDocumentsGrid(HttpRequest $request) {
        $grid = $this->getGridBuilder();

        $grid->createDataSourceFromQueryBuilder($this->documentManager->composeQueryForDocuments($this->getUserId(), $request->query['folderId'], true), 'documentId');

        return $grid;
    }

    public function handleSwitchFolder() {
        $folderId = $this->httpGet('folderId', true);
        $this->httpSessionSet('current_document_folder_id', $folderId);
        $this->redirect($this->createURL('list', ['folderId' => $folderId]));
    }
}

?>