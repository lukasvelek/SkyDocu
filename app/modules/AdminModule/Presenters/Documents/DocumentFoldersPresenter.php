<?php

namespace App\Modules\AdminModule;

use App\Constants\Container\CustomMetadataTypes;
use App\Constants\Container\SystemGroups;
use App\Core\Caching\CacheNames;
use App\Core\DB\DatabaseRow;
use App\Core\Http\HttpRequest;
use App\Exceptions\AException;
use App\UI\FormBuilder2\FormBuilder2;
use App\UI\FormBuilder\FormResponse;
use App\UI\GridBuilder2\Action;
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
        $grid = $this->componentFactory->getGridBuilder();

        $grid->createDataSourceFromQueryBuilder($this->folderManager->composeQueryForVisibleFoldersForUser($this->getUserId()), 'folderId');

        $grid->addColumnText('title', 'Title');

        $metadata = $grid->addAction('metadata');
        $metadata->setTitle('Metadata');
        $metadata->onCanRender[] = function() {
            return true;
        };
        $metadata->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
            $el = HTML::el('a')
                ->class('grid-link')
                ->href($this->createURLString('listMetadata', ['folderId' => $primaryKey]))
                ->text('Metadata');

            return $el;
        };

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
        $form = $this->componentFactory->getFormBuilder();

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
        $grid = $this->componentFactory->getGridBuilder();

        $grid->createDataSourceFromQueryBuilder($this->folderRepository->composeQueryForGroupRightsInFolder($request->query['folderId']), 'relationId');
        $grid->addQueryDependency('folderId', $request->query['folderId']);

        $col = $grid->addColumnText('groupId', 'Group');
        $col->onRenderColumn[] = function(DatabaseRow $row, Row $_row, Cell $cell, HTML $html, mixed $value) {
            $group = $this->groupRepository->getGroupById($value);

            if(array_key_exists($group['title'], SystemGroups::getAll())) {
                return SystemGroups::toString($group['title']);
            } else {
                return $group['title'];
            }
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
                ->href($this->createURLString('editFolderGroupRightsForm', ['folderId' => $request->query['folderId'], 'groupId' => $row->groupId]))
            ;

            return $el;
        };

        $delete = $grid->addAction('delete');
        $delete->setTitle('Delete');
        $delete->onCanRender[] = function(DatabaseRow $row, Row $_row, Action &$action) {
            $group = $this->groupRepository->getGroupById($row->groupId);

            return !($group['title'] == 'administrators');
        };
        $delete->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) use ($request) {
            $el = HTML::el('a')
                ->text('Delete')
                ->class('grid-link')
                ->href($this->createURLString('deleteFolderGroupRights', ['folderId' => $request->query['folderId'], 'groupId' => $row->groupId]))
            ;

            return $el;
        };

        return $grid;
    }

    public function handleDeleteFolderGroupRights() {
        $folderId = $this->httpGet('folderId', true);
        $groupId = $this->httpGet('groupId', true);

        try {
            $this->folderRepository->beginTransaction(__METHOD__);

            $this->folderManager->deleteGroupFolderRight($folderId, $groupId);

            $this->folderRepository->commit($this->getUserId(), __METHOD__);

            $this->flashMessage('Group rights deleted.', 'success');
        } catch(AException $e) {
            $this->folderRepository->rollback(__METHOD__);

            $this->flashMessage('Could not delete group rights. Reason: ' . $e->getMessage(), 'error', 10);
        }

        $this->redirect($this->createURL('listGroupRights', ['folderId' => $folderId]));
    }

    public function handleNewFolderGroupRightsForm(?FormResponse $fr = null) {
        if($fr !== null) {
            $folderId = $this->httpGet('folderId', true);

            try {
                $check = function(string $key) use ($fr) {
                    if(isset($fr->{$key}) && $fr->{$key} == 'on') {
                        return true;
                    } else {
                        return false;
                    }
                };

                $canView = $check('canView');
                $canCreate = $check('canCreate');
                $canEdit = $check('canEdit');
                $canDelete = $check('canDelete');

                if(!$canView && ($canCreate || $canEdit || $canDelete)) {
                    $canView = true;
                }

                $this->folderRepository->beginTransaction(__METHOD__);

                $this->folderManager->updateGroupFolderRight($folderId, $fr->group, $canView, $canCreate, $canEdit, $canDelete);

                $this->folderRepository->commit($this->getUserId(), __METHOD__);

                $this->flashMessage('New group added.', 'success');
            } catch(AException $e) {
                $this->folderRepository->rollback(__METHOD__);

                $this->flashMessage('Could not add new group. Reason: ' . $e->getMessage(), 'error', 10);
            }

            $this->redirect($this->createURL('listGroupRights', ['folderId' => $folderId]));
        }
    }

    public function renderNewFolderGroupRightsForm() {
        $this->template->links = [
            LinkBuilder::createSimpleLink('&larr; Back', $this->createURL('listGroupRights', ['folderId' => $this->httpGet('folderId')]), 'link')
        ];
    }

    protected function createComponentNewFolderGroupRightsForm(HttpRequest $request) {
        $groupsDb = $this->folderRepository->composeQueryForGroupRightsInFolder($request->query['folderId'])
            ->execute();

        $groups = [];
        while($row = $groupsDb->fetchAssoc()) {
            $groups[] = $row['groupId'];
        }

        $allGroupsDb = $this->groupRepository->composeQueryForGroups();
        $allGroupsDb->andWhere($allGroupsDb->getColumnNotInValues('groupId', $groups))->execute();

        $allGroups = [];
        while($row = $allGroupsDb->fetchAssoc()) {
            $title = $row['title'];

            if(array_key_exists($title, SystemGroups::getAll())) {
                $title = SystemGroups::toString($title);
            }

            $allGroups[] = [
                'value' => $row['groupId'],
                'text' => $title
            ];
        }

        $form = $this->componentFactory->getFormBuilder();

        $form->setAction($this->createURL('newFolderGroupRightsForm', ['folderId' => $request->query['folderId']]));

        $sel = $form->addSelect('group', 'Group')
            ->setRequired()
            ->addRawOptions($allGroups);

        $canView = $form->addCheckboxInput('canView', 'View:');
        $canCreate = $form->addCheckboxInput('canCreate', 'Create:');
        $canEdit = $form->addCheckboxInput('canEdit', 'Edit:');
        $canDelete = $form->addCheckboxInput('canDelete', 'Delete:');

        $submit = $form->addSubmit('Add');

        if(empty($allGroups)) {
            $sel->setDisabled();
            $sel->addRawOption('none', 'No groups available.', true);

            $canView->setDisabled();
            $canCreate->setDisabled();
            $canEdit->setDisabled();
            $canDelete->setDisabled();

            $submit->setDisabled();
        }

        return $form;
    }

    public function handleEditFolderGroupRightsForm(?FormResponse $fr = null) {
        if($fr !== null) {
            $folderId = $this->httpGet('folderId', true);
            $groupId = $this->httpGet('groupId', true);

            try {
                $check = function(string $key) use ($fr) {
                    if(isset($fr->{$key}) && $fr->{$key} == 'on') {
                        return true;
                    } else {
                        return false;
                    }
                };

                $canView = $check('canView');
                $canCreate = $check('canCreate');
                $canEdit = $check('canEdit');
                $canDelete = $check('canDelete');

                if(!$canView && ($canCreate || $canEdit || $canDelete)) {
                    $canView = true;
                }

                $this->folderRepository->beginTransaction(__METHOD__);

                $this->folderManager->updateGroupFolderRight($folderId, $groupId, $canView, $canCreate, $canEdit, $canDelete);

                $this->folderRepository->commit($this->getUserId(), __METHOD__);

                $this->flashMessage('Group updated.', 'success');
            } catch(AException $e) {
                $this->folderRepository->rollback(__METHOD__);

                $this->flashMessage('Could not update group. Reason: ' . $e->getMessage(), 'error', 10);
            }

            $this->redirect($this->createURL('listGroupRights', ['folderId' => $folderId]));
        }
    }

    public function renderEditFolderGroupRightsForm() {
        $this->template->links = $this->createBackUrl('listGroupRights', ['folderId' => $this->httpGet('folderId')], 'link');
    }

    protected function createComponentEditFolderGroupRightsForm(HttpRequest $request) {
        $row = $this->folderRepository->composeQueryForGroupRightsInFolder($request->query['folderId']);
        $row = $row->andWhere('groupId = ?', [$request->query['groupId']])
            ->execute()->fetch();

        $row = DatabaseRow::createFromDbRow($row);

        $group = $this->groupManager->getGroupById($request->query['groupId']);

        $form = $this->componentFactory->getFormBuilder();
        $form->setAction($this->createURL('editFolderGroupRightsForm', ['folderId' => $request->query['folderId'], 'groupId' => $request->query['groupId']]));

        $form->addSelect('group', 'Group')
            ->setRequired()
            ->addRawOption($group->groupId, $group->title, true)
            ->setDisabled();

        $form->addCheckboxInput('canView', 'View:')
            ->setChecked($row->canView);
        $form->addCheckboxInput('canCreate', 'Create:')
            ->setChecked($row->canCreate);
        $form->addCheckboxInput('canEdit', 'Edit:')
            ->setChecked($row->canEdit);
        $form->addCheckboxInput('canDelete', 'Delete:')
            ->setChecked($row->canDelete);

        $form->addSubmit('Save');

        return $form;
    }

    public function handleListMetadata() {
        $folderId = $this->httpGet('folderId', true);

        $links = [
            $this->createBackUrl('list'),
            LinkBuilder::createSimpleLink('Add metadata', $this->createURL('addMetadataToFolderForm', ['folderId' => $folderId]), 'link')
        ];

        $this->saveToPresenterCache('links', implode('&nbsp;&nbsp;', $links));
    }

    public function renderListMetadata() {
        $this->template->links = $this->loadFromPresenterCache('links');
    }

    protected function createComponentFolderMetadataGrid(HttpRequest $request) {
        $grid = $this->componentFactory->getGridBuilder();

        $grid->createDataSourceFromQueryBuilder($this->metadataManager->composeQueryForMetadataForFolder($request->query['folderId']), 'metadataId');
        $grid->addQueryDependency('folderId', $request->query['folderId']);

        $grid->addColumnText('title', 'Title');
        $grid->addColumnText('guiTitle', 'GUI title');
        $grid->addColumnConst('type', 'Type', CustomMetadataTypes::class);
        $grid->addColumnBoolean('isRequired', 'Is required');

        $delete = $grid->addAction('delete');
        $delete->setTitle('Delete');
        $delete->onCanRender[] = function() {
            return true;
        };
        $delete->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
            $el = HTML::el('a')
                ->text('Remove')
                ->href($this->createURLString('removeMetadataFromFolder', ['metadataId' => $row->customMetadataId, 'folderId' => $row->folderId]))
                ->class('grid-link');

            return $el;
        };

        return $grid;
    }

    public function handleAddMetadataToFolderForm(?FormResponse $fr = null) {
        $folderId = $this->httpGet('folderId', true);

        if($fr !== null) {
            try {
                $this->metadataRepository->beginTransaction(__METHOD__);

                $this->metadataManager->createMetadataFolderRight($fr->metadata, $folderId);

                $this->metadataRepository->commit($this->getUserId(), __METHOD__);

                $this->flashMessage('Metadata added to folder.', 'success');
            } catch(AException $e) {
                $this->metadataRepository->rollback(__METHOD__);

                $this->flashMessage('Could not add metadata to folder. Reason: ' . $e->getMessage(), 'error', 10);
            }

            $this->redirect($this->createURL('listMetadata', ['folderId' => $folderId]));
        }
    }

    public function renderAddMetadataToFolderForm() {
        $this->template->links = $this->createBackUrl('listMetadata', ['folderId' => $this->httpGet('folderId')]);
    }

    protected function createComponentAddMetadataToFolderForm(HttpRequest $request) {
        $qb = $this->metadataManager->composeQueryForMetadataNotInFolder($request->query['folderId']);
        $qb->execute();

        $metadataSelect = [];
        while($row = $qb->fetchAssoc()) {
            $row = DatabaseRow::createFromDbRow($row);

            $metadataSelect[] = [
                'value' => $row->metadataId,
                'text' => $row->guiTitle . ' (' . $row->title . ')'
            ];
        }

        $form = $this->componentFactory->getFormBuilder();

        $form->setAction($this->createURL('addMetadataToFolderForm', ['folderId' => $request->query['folderId']]));

        $form->addSelect('metadata', 'Metadata:')
            ->addRawOptions($metadataSelect)
            ->setDisabled(count($metadataSelect) == 0);

        $form->addSubmit('Add')
            ->setDisabled(count($metadataSelect) == 0);

        return $form;
    }

    public function handleRemoveMetadataFromFolder() {
        $folderId = $this->httpGet('folderId', true);
        $metadataId = $this->httpGet('metadataId', true);

        try {
            $this->metadataRepository->beginTransaction(__METHOD__);

            $this->metadataManager->removeMetadataFolderRight($metadataId, $folderId);

            $this->metadataRepository->commit($this->getUserId(), __METHOD__);

            $this->flashMessage('Metadata removed from folder.', 'success');
        } catch(AException $e) {
            $this->metadataRepository->rollback(__METHOD__);

            $this->flashMessage('Could not remove metadata from folder. Reason: ' . $e->getMessage(), 'error', 10);
        }

        $this->redirect($this->createURL('listMetadata', ['folderId' => $folderId]));
    }
}

?>