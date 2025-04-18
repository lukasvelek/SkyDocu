<?php

namespace App\Modules\AdminModule;

use App\Constants\Container\CustomMetadataTypes;
use App\Constants\Container\SystemGroups;
use App\Core\DB\DatabaseRow;
use App\Core\Http\FormRequest;
use App\Core\Http\HttpRequest;
use App\Exceptions\AException;
use App\Exceptions\RequiredAttributeIsNotSetException;
use App\Helpers\LinkHelper;
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
        $links = [];

        $newFolderLink = LinkBuilder::createSimpleLink('New folder', $this->createURL('newFolderForm'), 'link');
        $folderPathArray = [
            LinkBuilder::createSimpleLink('Home', $this->createURL('list'), 'link')
        ];

        if($this->httpRequest->get('folderId') !== null) {
            $folderId = $this->httpRequest->get('folderId');
            
            $newFolderLink = LinkBuilder::createSimpleLink('New folder', $this->createURL('newFolderForm', ['folderId' => $folderId]), 'link');
            
            $folderPathToRoot = $this->folderManager->getFolderPathToRoot($folderId);

            if(count($folderPathToRoot) >= MAX_CONTAINER_DOCUMENT_FOLDER_NESTING_LEVEL) {
                $newFolderLink = $this->createFlashMessage('info', 'Cannot create new folder, because the maximum nesting level was reached.', 0, false, true);
            }

            foreach($folderPathToRoot as $_folder) {
                $_folderId = $_folder->folderId;
                
                if($_folderId != $folderId) {
                    $folderPathArray[] = LinkBuilder::createSimpleLink($_folder->title, $this->createURL('list', ['folderId' => $_folderId]), 'link');
                } else {
                    $folderPathArray[] = '<span id="link">' . $_folder->title . '</span>';
                }
            }
        }
        
        $folderPath = implode(' > ', $folderPathArray);
        $links[] = $newFolderLink;

        $this->template->links = LinkHelper::createLinksFromArray($links);
        $this->template->folder_path = $folderPath;
    }

    protected function createComponentDocumentFoldersGrid(HttpRequest $request) {
        $grid = $this->componentFactory->getGridBuilder($this->containerId);

        $qb = $this->folderManager->composeQueryForVisibleFoldersForUser($this->getUserId());

        $isSubfolder = false;
        if($this->httpRequest->get('folderId') !== null) {
            $qb = $this->folderManager->composeQueryForSubfoldersForFolder($request->get('folderId'));
            $isSubfolder = true;
        }

        $grid->createDataSourceFromQueryBuilder($qb, 'folderId');

        $grid->addColumnText('title', 'Title');

        $subfolders = $grid->addAction('subfolders');
        $subfolders->setTitle('Subfolders');
        $subfolders->onCanRender[] = function(DatabaseRow $row) {
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

        $metadata = $grid->addAction('metadata');
        $metadata->setTitle('Metadata');
        $metadata->onCanRender[] = function() {
            return true;
        };
        $metadata->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
            $params = ['folderId' => $primaryKey];

            if($row->parentFolderId !== null) {
                $params['parentFolderId'] = $row->parentFolderId;
            }

            $el = HTML::el('a')
                ->class('grid-link')
                ->href($this->createURLString('listMetadata', $params))
                ->text('Metadata');

            return $el;
        };

        $groupRights = $grid->addAction('groupRights');
        $groupRights->setTitle('Group rights');
        $groupRights->onCanRender[] = function(DatabaseRow $row, Row $_row) use ($isSubfolder) {
            return !$isSubfolder;
        };
        $groupRights->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
            $params = ['folderId' => $primaryKey];

            if($row->parentFolderId !== null) {
                $params['parentFolderId'] = $row->parentFolderId;
            }

            $el = HTML::el('a')
                ->class('grid-link')
                ->href($this->createURLString('listGroupRights', $params))
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

            if(count($this->folderManager->getSubfoldersForFolder($row->folderId)) > 0) {
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

    public function handleDeleteFolder() {
        $folderId = $this->httpRequest->get('folderId');
        $parentFolderId = $this->httpRequest->get('parentFolderId'); // for returning purposes

        try {
            $this->folderRepository->beginTransaction(__METHOD__);

            $this->folderManager->deleteFolder($folderId, $this->getUserId());

            $this->folderRepository->commit($this->getUserId(), __METHOD__);

            $this->flashMessage('Folder deleted.', 'success');
        } catch(AException $e) {
            $this->folderRepository->rollback(__METHOD__);

            $this->flashMessage('Could not delete folder. Reason: ' . $e->getMessage(), 'error', 10);
        }

        if($parentFolderId !== null) {
            $this->redirect($this->createURL('list', ['folderId' => $parentFolderId]));
        } else {
            $this->redirect($this->createURL('list'));
        }
    }

    public function handleNewFolderForm(?FormRequest $fr = null) {
        $folderId = $this->httpRequest->get('folderId');

        if($folderId === null) {
            throw new RequiredAttributeIsNotSetException('folderId');
        }

        if($fr !== null) {
            try {
                $this->folderRepository->beginTransaction(__METHOD__);

                $this->folderManager->createNewFolder($fr->title, $this->getUserId(), $folderId);

                $this->folderRepository->commit($this->getUserId(), __METHOD__);

                $this->flashMessage('Folder created.', 'success');
            } catch(AException $e) {
                $this->folderRepository->rollback(__METHOD__);
                
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
        $folderId = $this->httpRequest->get('folderId');

        $backLink = '';
        if($folderId !== null) {
            $backLink = $this->createBackUrl('list', ['folderId' => $folderId]);
        } else {
            $backLink = $this->createBackUrl('list');
        }

        $this->template->links = $backLink;
    }

    protected function createComponentNewDocumentFolderForm(HttpRequest $request) {
        $url = '';
        if($request->get('folderId') !== null) {
            $url = $this->createURL('newFolderForm', ['folderId' => $request->get('folderId')]);
        } else {
            $url = $this->createURL('newFolderForm');
        }

        $form = $this->componentFactory->getFormBuilder();

        $form->setAction($url);

        $form->addTextInput('title', 'Title:')
            ->setRequired();

        $form->addSubmit('Create');

        return $form;
    }

    public function renderListGroupRights() {
        $folderId = $this->httpRequest->get('folderId');

        if($folderId === null) {
            throw new RequiredAttributeIsNotSetException('folderId');
        }

        $parentFolderId = $this->httpRequest->get('parentFolderId') ?? $folderId;
        $folder = $this->folderManager->getFolderById($folderId);

        $params = ['folderId' => $folderId];
        if($parentFolderId != $folderId) {
            $params['parentFolderId'] = $parentFolderId;
        }

        $links = [
            LinkBuilder::createSimpleLink('&larr; Back', $this->createURL('list', ['folderId' => $parentFolderId]), 'link'),
            LinkBuilder::createSimpleLink('Add group', $this->createURL('newFolderGroupRightsForm', $params), 'link')
        ];

        $this->template->folder_title = $folder->title;
        $this->template->links = LinkHelper::createLinksFromArray($links);
    }

    protected function createComponentDocumentFoldersGroupRightsGrid(HttpRequest $request) {
        $grid = $this->componentFactory->getGridBuilder($this->containerId);

        $grid->createDataSourceFromQueryBuilder($this->folderRepository->composeQueryForGroupRightsInFolder($request->get('folderId')), 'relationId');
        $grid->addQueryDependency('folderId', $request->get('folderId'));

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
            $params = ['folderId' => $request->get('folderId'), 'groupId' => $row->groupId];
            if($this->httpRequest->get('parentFolderId') !== null) {
                $params['parentFolderId'] = $request->get('parentFolderId');
            }

            $el = HTML::el('a')
                ->text('Edit')
                ->class('grid-link')
                ->href($this->createURLString('editFolderGroupRightsForm', $params))
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
            $params = ['folderId' => $request->get('folderId'), 'groupId' => $row->groupId];
            if($this->httpRequest->get('parentFolderId') !== null) {
                $params['parentFolderId'] = $request->get('parentFolderId');
            }

            $el = HTML::el('a')
                ->text('Delete')
                ->class('grid-link')
                ->href($this->createURLString('deleteFolderGroupRights', $params))
            ;

            return $el;
        };

        return $grid;
    }

    public function handleDeleteFolderGroupRights() {
        $folderId = $this->httpRequest->get('folderId');
        if($folderId === null) {
            throw new RequiredAttributeIsNotSetException('folderId');
        }
        $parentFolderId = $this->httpRequest->get('parentFolderId') ?? $folderId;
        $groupId = $this->httpRequest->get('groupId');
        if($groupId === null) {
            throw new RequiredAttributeIsNotSetException('groupId');
        }

        try {
            $this->folderRepository->beginTransaction(__METHOD__);

            $this->folderManager->deleteGroupFolderRight($folderId, $groupId);

            $this->folderRepository->commit($this->getUserId(), __METHOD__);

            $this->flashMessage('Group rights deleted.', 'success');
        } catch(AException $e) {
            $this->folderRepository->rollback(__METHOD__);

            $this->flashMessage('Could not delete group rights. Reason: ' . $e->getMessage(), 'error', 10);
        }

        $params = ['folderId' => $folderId];
        if($parentFolderId != $folderId) {
            $params['parentFolderId'] = $parentFolderId;
        }

        $this->redirect($this->createURL('listGroupRights', $params));
    }

    public function handleNewFolderGroupRightsForm(?FormRequest $fr = null) {
        if($fr !== null) {
            $folderId = $this->httpRequest->get('folderId');
            if($folderId === null) {
                throw new RequiredAttributeIsNotSetException('folderId');
            }
            $parentFolderId = $this->httpRequest->get('parentFolderId') ?? $folderId;

            try {
                $check = function(string $key) use ($fr) {
                    if($fr->isset($key) && $fr->{$key} == 'on') {
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

            $params = ['folderId' => $folderId];
            if($parentFolderId != $folderId) {
                $params['parentFolderId'] = $parentFolderId;
            }

            $this->redirect($this->createURL('listGroupRights', $params));
        }
    }

    public function renderNewFolderGroupRightsForm() {
        $folderId = $this->httpRequest->get('folderId');
        if($folderId === null) {
            throw new RequiredAttributeIsNotSetException('folderId');
        }
        $parentFolderId = $this->httpRequest->get('parentFolderId') ?? $folderId;

        $params = ['folderId' => $folderId];
        if($parentFolderId != $folderId) {
            $params['parentFolderId'] = $parentFolderId;
        }

        $links = [
            LinkBuilder::createSimpleLink('&larr; Back', $this->createURL('listGroupRights', $params), 'link')
        ];

        $this->template->links = LinkHelper::createLinksFromArray($links);
    }

    protected function createComponentNewFolderGroupRightsForm(HttpRequest $request) {
        $groupsDb = $this->folderRepository->composeQueryForGroupRightsInFolder($request->get('folderId'))
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

        $params = ['folderId' => $request->get('folderId')];
        if($this->httpRequest->get('parentFolderId') !== null) {
            $params['parentFolderId'] = $request->get('parentFolderId');
        }

        $form->setAction($this->createURL('newFolderGroupRightsForm', $params));

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

    public function handleEditFolderGroupRightsForm(?FormRequest $fr = null) {
        if($fr !== null) {
            $folderId = $this->httpRequest->get('folderId');
            if($folderId === null) {
                throw new RequiredAttributeIsNotSetException('folderId');
            }
            $parentFolderId = $this->httpRequest->get('parentFolderId') ?? $folderId;
            $groupId = $this->httpRequest->get('groupId');
            if($groupId === null) {
                throw new RequiredAttributeIsNotSetException('groupId');
            }

            try {
                $check = function(string $key) use ($fr) {
                    if($fr->isset($key) && $fr->{$key} == 'on') {
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
            
            $params = ['folderId' => $folderId];
            if($parentFolderId != $folderId) {
                $params['parentFolderId'] = $parentFolderId;
            }

            $this->redirect($this->createURL('listGroupRights', $params));
        }
    }

    public function renderEditFolderGroupRightsForm() {
        $folderId = $this->httpRequest->get('folderId');
        if($folderId === null) {
            throw new RequiredAttributeIsNotSetException('folderId');
        }
        $parentFolderId = $this->httpRequest->get('parentFolderId') ?? $folderId;

        $params = ['folderId' => $folderId];
        if($parentFolderId != $folderId) {
            $params['parentFolderId'] = $parentFolderId;
        }

        $this->template->links = $this->createBackUrl('listGroupRights', $params, 'link');
    }

    protected function createComponentEditFolderGroupRightsForm(HttpRequest $request) {
        $row = $this->folderRepository->composeQueryForGroupRightsInFolder($request->get('folderId'));
        $row = $row->andWhere('groupId = ?', [$request->get('groupId')])
            ->execute()->fetch();

        $row = DatabaseRow::createFromDbRow($row);

        $group = $this->groupManager->getGroupById($request->get('groupId'));

        $form = $this->componentFactory->getFormBuilder();
        
        $params = ['folderId' => $request->get('folderId'), 'groupId' => $request->get('folderId')];
        if($this->httpRequest->get('parentFolderId') !== null) {
            $params['parentFolderId'] = $request->get('parentFolderId');
        }

        $form->setAction($this->createURL('editFolderGroupRightsForm', $params));

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

    public function renderListMetadata() {
        $folderId = $this->httpRequest->get('folderId');
        if($folderId === null) {
            throw new RequiredAttributeIsNotSetException('folderId');
        }
        $parentFolderId = $this->httpRequest->get('parentFolderId') ?? $folderId;

        $params = ['folderId' => $folderId];
        if($parentFolderId != $folderId) {
            $params['parentFolderId'] = $parentFolderId;
        }

        $links = [
            $this->createBackUrl('list', ['folderId' => $parentFolderId]),
            LinkBuilder::createSimpleLink('Add metadata', $this->createURL('addMetadataToFolderForm', $params), 'link')
        ];

        $this->template->links = LinkHelper::createLinksFromArray($links);
    }

    protected function createComponentFolderMetadataGrid(HttpRequest $request) {
        $grid = $this->componentFactory->getGridBuilder($this->containerId);

        $grid->createDataSourceFromQueryBuilder($this->metadataManager->composeQueryForMetadataForFolder($request->get('folderId')), 'metadataId');
        $grid->addQueryDependency('folderId', $request->get('folderId'));

        $grid->addColumnText('title', 'Title');
        $grid->addColumnText('guiTitle', 'GUI title');
        $grid->addColumnConst('type', 'Type', CustomMetadataTypes::class);
        $grid->addColumnBoolean('isRequired', 'Is required');

        $delete = $grid->addAction('delete');
        $delete->setTitle('Delete');
        $delete->onCanRender[] = function() {
            return true;
        };
        $delete->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) use ($request) {
            $params = ['metadataId' => $row->customMetadataId, 'folderId' => $row->folderId];
            if($this->httpRequest->get('parentFolderId') !== null) {
                $params['parentFolderId'] = $request->get('parentFolderId');
            }

            $el = HTML::el('a')
                ->text('Remove')
                ->href($this->createURLString('removeMetadataFromFolder', $params))
                ->class('grid-link');

            return $el;
        };

        return $grid;
    }

    public function handleAddMetadataToFolderForm(?FormRequest $fr = null) {
        $folderId = $this->httpRequest->get('folderId');
        if($folderId === null) {
            throw new RequiredAttributeIsNotSetException('folderId');
        }
        $parentFolderId = $this->httpRequest->get('parentFolderId') ?? $folderId;

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

            $params = ['folderId' => $folderId];
            if($parentFolderId != $folderId) {
                $params['parentFolderId'] = $parentFolderId;
            }

            $this->redirect($this->createURL('listMetadata', $params));
        }
    }

    public function renderAddMetadataToFolderForm() {
        $folderId = $this->httpRequest->get('folderId');
        if($folderId === null) {
            throw new RequiredAttributeIsNotSetException('folderId');
        }
        $parentFolderId = $this->httpRequest->get('parentFolderId') ?? $folderId;

        $params = ['folderId' => $folderId];
        if($parentFolderId != $folderId) {
            $params['parentFolderId'] = $parentFolderId;
        }
        $this->template->links = $this->createBackUrl('listMetadata', $params);
    }

    protected function createComponentAddMetadataToFolderForm(HttpRequest $request) {
        $qb = $this->metadataManager->composeQueryForMetadataNotInFolder($request->get('folderId'));
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

        $params = ['folderId' => $request->get('folderId')];
        if($this->httpRequest->get('parentFolderId') !== null) {
            $params['parentFolderId'] = $request->get('parentFolderId');
        }

        $form->setAction($this->createURL('addMetadataToFolderForm', ['folderId' => $request->get('folderId')]));

        $form->addSelect('metadata', 'Metadata:')
            ->addRawOptions($metadataSelect)
            ->setDisabled(count($metadataSelect) == 0);

        $form->addSubmit('Add')
            ->setDisabled(count($metadataSelect) == 0);

        return $form;
    }

    public function handleRemoveMetadataFromFolder() {
        $folderId = $this->httpRequest->get('folderId');
        if($folderId === null) {
            throw new RequiredAttributeIsNotSetException('folderId');
        }
        $parentFolderId = $this->httpRequest->get('parentFolderId') ?? $folderId;
        $metadataId = $this->httpRequest->get('metadataId');
        if($metadataId === null) {
            throw new RequiredAttributeIsNotSetException('metadataId');
        }

        try {
            $this->metadataRepository->beginTransaction(__METHOD__);

            $this->metadataManager->removeMetadataFolderRight($metadataId, $folderId);

            $this->metadataRepository->commit($this->getUserId(), __METHOD__);

            $this->flashMessage('Metadata removed from folder.', 'success');
        } catch(AException $e) {
            $this->metadataRepository->rollback(__METHOD__);

            $this->flashMessage('Could not remove metadata from folder. Reason: ' . $e->getMessage(), 'error', 10);
        }

        $params = ['folderId' => $folderId];
        if($parentFolderId != $folderId) {
            $params['parentFolderId'] = $parentFolderId;
        }

        $this->redirect($this->createURL('listMetadata', ['folderId' => $folderId]));
    }
}

?>