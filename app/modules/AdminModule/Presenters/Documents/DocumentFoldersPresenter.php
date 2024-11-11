<?php

namespace App\Modules\AdminModule;

use App\Core\DB\DatabaseRow;
use App\Core\Http\HttpRequest;
use App\Exceptions\AException;
use App\UI\FormBuilder2\FormBuilder2;
use App\UI\FormBuilder\FormResponse;
use App\UI\GridBuilder2\Cell;
use App\UI\GridBuilder2\Row;
use App\UI\HTML\HTML;
use App\UI\LinkBuilder;

class DocumentFoldersPresenter extends AAdminPresenter {
    public function __construct() {
        parent::__construct('DocumentFoldersPresenter', 'Document folders');

        $this->setDocuments();
    }

    public function renderList() {
        $this->template->links = [
            LinkBuilder::createSimpleLink('New folder', $this->createURL('newFolderForm'), 'link')
        ];
    }

    protected function createComponentDocumentFoldersGrid(HttpRequest $request) {
        $grid = $this->getGridBuilder();

        $grid->createDataSourceFromQueryBuilder($this->folderManager->composeQueryForVisibleFoldersForUser($this->getUserId()), 'folderId');

        $grid->addColumnText('title', 'Title');

        $groupRights = $grid->addAction('groupRights');
        $groupRights->setTitle('Group rights');
        $groupRights->onCanRender[] = function(DatabaseRow $row, Row $_row) {
            return true;
        };
        $groupRights->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
            $el = HTML::el('a')
                ->class('grid-link')
                ->href($this->createURLString('listGroupRights', ['folderId' => $primaryKey]))
                ->text('Group rights');

            return $el;
        };

        $deleteFolder = $grid->addAction('deleteFolder');
        $deleteFolder->setTitle('Delete folder');
        $deleteFolder->onCanRender[] = function(DatabaseRow $row, Row $_row) {
            if($row->isSystem == true) {
                return false;
            }
            
            if($this->documentManager->getDocumentCountForFolder($row->folderId) > 0) {
                return false;
            }

            return true;
        };
        $deleteFolder->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
            $el = HTML::el('a')
                    ->class('grid-link')
                    ->href($this->createURLString('deleteFolder', ['folderId' => $primaryKey]))
                    ->text('Delete');

            return $el;
        };

        return $grid;
    }

    public function handleNewFolderForm(?FormResponse $fr = null) {
        if($fr !== null) {
            try {
                $this->folderRepository->beginTransaction(__METHOD__);

                $this->folderManager->createNewFolder($fr->title, $this->getUserId());

                $this->folderRepository->commit($this->getUserId(), __METHOD__);

                $this->flashMessage('Folder created.', 'success', 5);
            } catch(AException $e) {
                $this->folderRepository->rollback(__METHOD__);
                
                $this->flashMessage('Could not create new folder.', 'error', 10);
            }

            $this->redirect($this->createURL('list'));
        }
    }

    public function renderNewFolderForm() {
        $this->template->links = [
            LinkBuilder::createSimpleLink('&larr; Back', $this->createURL('list'), 'link')
        ];
    }

    protected function createComponentNewDocumentFolderForm(HttpRequest $request) {
        $form = new FormBuilder2($request);

        $form->setAction($this->createURL('newFolderForm'));

        $form->addTextInput('title', 'Title:')
            ->setRequired();

        $form->addSubmit('Create');

        return $form;
    }

    public function handleListGroupRights() {
        $folderId = $this->httpGet('folderId', true);

        $folder = $this->folderManager->getFolderById($folderId);

        $this->saveToPresenterCache('folderTitle', $folder->title);

        $links = [
            LinkBuilder::createSimpleLink('&larr; Back', $this->createURL('list'), 'link'),
            LinkBuilder::createSimpleLink('Add group', $this->createURL('newFolderGroupRightsForm', ['folderId' => $folderId]), 'link')
        ];

        $this->saveToPresenterCache('links', implode('&nbsp;&nbsp;', $links));
    }

    public function renderListGroupRights() {
        $this->template->folder_title = $this->loadFromPresenterCache('folderTitle');
        $this->template->links = $this->loadFromPresenterCache('links');
    }

    protected function createComponentDocumentFoldersGroupRightsGrid(HttpRequest $request) {
        $grid = $this->getGridBuilder();

        $grid->createDataSourceFromQueryBuilder($this->folderRepository->composeQueryForGroupRightsInFolder($request->query['folderId']), 'relationId');
        $grid->addQueryDependency('folderId', $request->query['folderId']);

        $col = $grid->addColumnText('groupId', 'Group');
        $col->onRenderColumn[] = function(DatabaseRow $row, Row $_row, Cell $cell, HTML $html, mixed $value) {
            $group = $this->groupRepository->getGroupById($value);

            return $group['title'];
        };
        
        $grid->addColumnBoolean('canView', 'View');
        $grid->addColumnBoolean('canCreate', 'Create');
        $grid->addColumnBoolean('canEdit', 'Edit');
        $grid->addColumnBoolean('canDelete', 'Delete');

        $edit = $grid->addAction('edit');
        $edit->setTitle('Edit');
        $edit->onCanRender[] = function() {
            return true;
        };
        $edit->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) use ($request) {
            $el = HTML::el('a')
                ->text('Edit')
                ->class('grid-link')
                ->href($this->createURLString('editFolderGroupRightsForm', ['folderId' => $request->query['folderId'], 'groupId' => $primaryKey]))
            ;

            return $el;
        };

        return $grid;
    }

    public function renderNewFolderGroupRightsForm() {
        $this->template->links = [
            LinkBuilder::createSimpleLink('&larr; Back', $this->createURL('listGroupRights', ['folderId' => $this->httpGet('folderId')]), 'link')
        ];
    }

    protected function createComponentNewFolderGroupRightsForm(HttpRequest $request) {
        $groups = $this->folderRepository->composeQueryForGroupRightsInFolder($request->query['folderId']);

        $form = new FormBuilder2($request);

        $form->setAction($this->createURL('newFolderGroupRightsForm', [$request->query['folderId']]));

        $form->addSelect('group', 'Group')
            ->setRequired();

        return $form;
    }
}

?>