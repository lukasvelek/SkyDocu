<?php

namespace App\Modules\AdminModule;

use App\Core\DB\DatabaseRow;
use App\Core\Http\FormRequest;
use App\Core\Http\HttpRequest;
use App\Exceptions\AException;
use App\UI\GridBuilder2\Action;
use App\UI\GridBuilder2\Row;
use App\UI\HTML\HTML;
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
            $qb->andWhere('parentFolderId = ?', [$this->httpRequest->query('folderId')]);
        }

        $grid->createDataSourceFromQueryBuilder($qb, 'folderId');

        $grid->addColumnText('title', 'Title');

        $subfolders = $grid->addAction('subfolders');
        $subfolders->setTitle('Subfolders');
        $subfolders->onCanRender[] = function(DatabaseRow $row, Row $_row, Action &$action) {
            return true;
        };
        $subfolders->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
            $params = ['folderId' => $primaryKey];

            if($row->parentFolderId !== null) {
                $params['parentFolderId'] = $row->parentFolderId;
            }

            $el = HTML::el('a')
                ->class('grid-link')
                ->href($this->createURLString('list', $params))
                ->text('Subfolders');

            return $el;
        };

        $deleteFolder = $grid->addAction('deleteFolder');
        $deleteFolder->setTitle('Delete folder');
        $deleteFolder->onCanRender[] = function(DatabaseRow $row, Row $_row, Action &$action) {
            if($row->isSystem === true) {
                return false;
            }

            if(count($this->archiveManager->getDocumentsForArchiveFolder($row->folderId)) > 0) {
                return false;
            }

            if(count($this->archiveManager->getSubfoldersForArchiveFolder($row->folderId)) > 0) {
                return false;
            }

            return true;
        };
        $deleteFolder->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
            $params = ['folderId' => $primaryKey];

            if($row->parentFolderId !== null) {
                $params['parentFolderId'] = $row->parentFolderId;
            }

            $el = HTML::el('a')
                    ->class('grid-link')
                    ->href($this->createURLString('deleteFolder', $params))
                    ->text('Delete');

            return $el;
        };

        return $grid;
    }

    public function handleNewFolderForm(?FormRequest $fr = null) {
        $folderId = $this->httpRequest->query('folderId');

        if($fr !== null) {
            try {
                $this->archiveRepository->beginTransaction(__METHOD__);

                $this->archiveManager->createNewArchiveFolder($fr->title, $folderId);

                $this->archiveRepository->commit($this->getUserId(), __METHOD__);

                $this->flashMessage('Folder created.', 'success');
            } catch(AException $e) {
                $this->archiveRepository->rollback(__METHOD__);

                $this->flashMessage('Could not create a new folder. Reason: ' . $e->getMessage(), 'error', 10);
            }

            if($folderId !== null) {
                $this->redirect($this->createURL('list', ['folderId' => $folderId]));
            } else {
                $this->redirect($this->createURL('list'));
            }
        }
    }

    public function renderNewFolderForm() {
        if($this->httpRequest->query('folderId') !== null) {
            $this->template->links = $this->createBackUrl('list', ['folderId' => $this->httpRequest->query('folderId')]);
        } else {
            $this->template->links = $this->createBackUrl('list');
        }
    }

    protected function createComponentNewArchiveFolderForm(HttpRequest $request) {
        $form = $this->componentFactory->getFormBuilder();

        $url = '';
        if($request->query('folderId') !== null) {
            $url = $this->createURL('newFolderForm', ['folderId' => $request->query('folderId')]);
        } else {
            $url = $this->createURL('newFolderForm');
        }

        $form->setAction($url);

        $form->addTextInput('title', 'Title:')
            ->setRequired();

        $form->addSubmit('Create');

        return $form;
    }
}

?>