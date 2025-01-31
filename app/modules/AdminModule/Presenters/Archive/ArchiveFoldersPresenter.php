<?php

namespace App\Modules\AdminModule;

use App\Core\Http\HttpRequest;
use App\UI\LinkBuilder;

class ArchiveFoldersPresenter extends AAdminPresenter {
    public function __construct() {
        parent::__construct('ArchiveFoldersPresenter', 'Archive folders');

        $this->setArchive();
    }

    public function handleList() {
        $links = [];

        $newFolderLink = LinkBuilder::createSimpleLink('New folder', $this->createURL('newFolderForm'), 'link');
        $folderPathArray = [
            LinkBuilder::createSimpleLink('Home', $this->createURL('list'), 'link')
        ];

        if($this->httpRequest->query('folderId') !== null) {
            $folderId = $this->httpRequest->query('folderId');

            $newFolderLink = LinkBuilder::createSimpleLink('New folder', $this->createURL('newFolderForm', ['folderId' => $folderId]), 'link');

            $folderPathToRoot = $this->archiveManager->getArchiveFolderPathToRoot($folderId);

            foreach($folderPathToRoot as $_folder) {
                $_folderId = $_folder->folderId;

                if($_folderId == $folderId) {
                    // current folder
                    $folderPathArray[] = '<span id="link">' . $_folder->title . '</span>';
                } else {
                    $folderPathArray[] = LinkBuilder::createSimpleLink($_folder->title, $this->createURL('list', ['folderId' => $_folderId]), 'link');
                }
            }

            if(count($folderPathToRoot) >= MAX_CONTAINER_ARCHIVE_FOLDER_NESTING_LEVEL) {
                $newFolderLink = $this->createFlashMessage('info', 'Cannot create new folder, because the maximum nesting level was reached.', 0, false, true);
            }
        }
        
        $folderPath = implode(' > ', $folderPathArray);
        $links[] = $newFolderLink;

        $this->saveToPresenterCache('links', $links);
        $this->saveToPresenterCache('folderPath', $folderPath);
    }

    public function renderList() {
        $this->template->links = $this->loadFromPresenterCache('links');
        $this->template->folder_path = $this->loadFromPresenterCache('folderPath');
    }

    protected function createComponentArchiveFoldersGrid(HttpRequest $request) {
        $grid = $this->componentFactory->getGridBuilder($this->containerId);

        $qb = $this->archiveRepository->composeQueryForArchiveFolders();

        if($this->httpRequest->query('folderId') !== null) {
            $qb->andWhere('folderId = ?', [$this->httpRequest->query('folderId')]);
        }

        $grid->createDataSourceFromQueryBuilder($qb, 'folderId');

        $grid->addColumnText('title', 'Title');

        return $grid;
    }
}

?>