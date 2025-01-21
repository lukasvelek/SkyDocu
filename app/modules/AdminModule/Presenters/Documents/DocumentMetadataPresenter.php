<?php

namespace App\Modules\AdminModule;

use App\Components\DocumentMetadataForm\DocumentMetadataForm;
use App\Constants\Container\CustomMetadataTypes;
use App\Core\DB\DatabaseRow;
use App\Core\Http\FormRequest;
use App\Core\Http\HttpRequest;
use App\Exceptions\AException;
use App\Exceptions\RequiredAttributeIsNotSetException;
use App\UI\GridBuilder2\Action;
use App\UI\GridBuilder2\Cell;
use App\UI\GridBuilder2\Row;
use App\UI\HTML\HTML;
use App\UI\LinkBuilder;

class DocumentMetadataPresenter extends AAdminPresenter {
    public function __construct() {
        parent::__construct('DocumentMetadataPresenter', 'Document metadata');

        $this->setDocuments();
    }

    public function handleList() {
        $links = [
            LinkBuilder::createSimpleLink('New metadata', $this->createURL('newMetadataForm'), 'link')
        ];

        $this->saveToPresenterCache('links', implode('&nbsp;&nbsp;', $links));
    }

    public function renderList() {
        $this->template->links = $this->loadFromPresenterCache('links');
    }

    protected function createComponentMetadataGrid() {
        $grid = $this->componentFactory->getGridBuilder($this->containerId);

        $grid->createDataSourceFromQueryBuilder($this->metadataRepository->composeQueryForMetadata(), 'metadataId');

        $grid->addColumnText('title', 'Title');
        $grid->addColumnText('guiTitle', 'GUI title');
        $grid->addColumnConst('type', 'Type', CustomMetadataTypes::class);
        $grid->addColumnBoolean('isRequired', 'Is required');

        $enumList = $grid->addAction('enumList');
        $enumList->setTitle('Values');
        $enumList->onCanRender[] = function(DatabaseRow $row, Row $_row) {
            return $row->type == CustomMetadataTypes::ENUM;
        };
        $enumList->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
            $el = HTML::el('a')
                ->text('Values')
                ->href($this->createURLString('listEnumValues', ['metadataId' => $primaryKey]))
                ->class('grid-link');

            return $el;
        };

        $folderRights = $grid->addAction('folderRights');
        $folderRights->setTitle('Folders');
        $folderRights->onCanRender[] = function() {
            return true;
        };
        $folderRights->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
            $el = HTML::el('a')
                ->text('Folders')
                ->href($this->createURLString('listFolderRights', ['metadataId' => $primaryKey]))
                ->class('grid-link');

            return $el;
        };

        $edit = $grid->addAction('edit');
        $edit->setTitle('Edit');
        $edit->onCanRender[] = function() {
            return true;
        };
        $edit->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
            $el = HTML::el('a')
                ->text('Edit')
                ->href($this->createURLString('editMetadataForm', ['metadataId' => $primaryKey]))
                ->class('grid-link');
            
            return $el;
        };

        $delete = $grid->addAction('delete');
        $delete->setTitle('Delete');
        $delete->onCanRender[] = function() {
            return true;
        };
        $delete->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
            $el = HTML::el('a')
                ->text('Delete')
                ->href($this->createURLString('deleteMetadata', ['metadataId' => $primaryKey]))
                ->class('grid-link');
            
            return $el;
        };

        return $grid;
    }

    public function handleEditMetadataForm(?FormRequest $fr = null) {
        if($fr !== null) {
            $metadataId = $this->httpRequest->query('metadataId');
            if($metadataId === null) {
                throw new RequiredAttributeIsNotSetException('metadataId');
            }

            $defaultValue = null;

            switch($fr->type) {
                case CustomMetadataTypes::BOOL:
                    if(isset($fr->defaultValueBoolean) && $fr->defaultValueBoolean !== '') {
                        $defaultValue = $fr->defaultValueBoolean;
                    }
                    break;
                
                case CustomMetadataTypes::DATETIME:
                    if(isset($fr->defaultValueDatetime) && $fr->defaultValueDatetime !== '') {
                        $defaultValue = $fr->defaultValueDatetime;
                    }
                    break;

                case CustomMetadataTypes::NUMBER:
                    if(isset($fr->defaultValueNumber) && $fr->defaultValueNumber !== '') {
                        $defaultValue = $fr->defaultValueNumber;
                    }
                    break;

                case CustomMetadataTypes::TEXT:
                    if(isset($fr->defaultValue) && $fr->defaultValue !== '') {
                        $defaultValue = $fr->defaultValue;
                    }
                    break;
            }
            
            $isRequired = false;

            if(isset($fr->isRequired) && $fr->isRequired == 'on') {
                $isRequired = true;
            }

            $data = [
                'title' => $fr->title,
                'guiTitle' => $fr->guiTitle,
                'type' => $fr->type,
                'defaultValue' => $defaultValue,
                'isRequired' => $isRequired ? 1 : 0
            ];

            try {
                $this->metadataRepository->beginTransaction(__METHOD__);

                $this->metadataManager->updateMetadata($metadataId, $data);

                $this->metadataRepository->commit($this->getUserId(), __METHOD__);

                $this->flashMessage('Metadata updated.', 'success');
            } catch(AException $e) {
                $this->metadataRepository->rollback(__METHOD__);

                $this->flashMessage('Could not update metadata. Reason: ' . $e->getMessage(), 'error', 10);
            }

            $this->redirect($this->createURL('list'));
        }
    }

    public function renderEditMetadataForm() {
        $this->template->links = $this->createBackUrl('list');
    }

    protected function createComponentEditDocumentMetadataForm(HttpRequest $request) {
        $metadata = $this->metadataManager->getMetadataById($request->query['metadataId']);

        $form = new DocumentMetadataForm($request);

        $form->setMetadata($metadata);
        $form->setAction($this->createURL('editMetadataForm', ['metadataId' => $request->query['metadataId']]));

        return $form;
    }

    public function handleNewMetadataForm(?FormRequest $fr = null) {
        if($fr !== null) {
            $defaultValue = null;

            switch($fr->type) {
                case CustomMetadataTypes::BOOL:
                    if(isset($fr->defaultValueBoolean) && $fr->defaultValueBoolean !== '') {
                        $defaultValue = $fr->defaultValueBoolean;
                    }
                    break;
                
                case CustomMetadataTypes::DATETIME:
                    if(isset($fr->defaultValueDatetime) && $fr->defaultValueDatetime !== '') {
                        $defaultValue = $fr->defaultValueDatetime;
                    }
                    break;

                case CustomMetadataTypes::NUMBER:
                    if(isset($fr->defaultValueNumber) && $fr->defaultValueNumber !== '') {
                        $defaultValue = $fr->defaultValueNumber;
                    }
                    break;

                case CustomMetadataTypes::TEXT:
                    if(isset($fr->defaultValue) && $fr->defaultValue !== '') {
                        $defaultValue = $fr->defaultValue;
                    }
                    break;
            }
            
            $isRequired = false;

            if(isset($fr->isRequired) && $fr->isRequired == 'on') {
                $isRequired = true;
            }

            try {
                $this->metadataRepository->beginTransaction(__METHOD__);

                $this->metadataManager->createNewMetadata($fr->title, $fr->guiTitle, $fr->type, $defaultValue, $isRequired);

                $this->metadataRepository->commit($this->getUserId(), __METHOD__);

                $this->flashMessage('Metadata created.', 'success');
            } catch(AException $e) {
                $this->metadataRepository->rollback(__METHOD__);

                $this->flashMessage('Could not create new metadata. Reason: ' . $e->getMessage(), 'error', 10);
            }

            $this->redirect($this->createURL('list'));
        }
    }

    public function renderNewMetadataForm() {
        $this->template->links = $this->createBackUrl('list');
    }

    protected function createComponentNewDocumentMetadataForm(HttpRequest $request) {
        $form = new DocumentMetadataForm($request);

        $form->setAction($this->createURL('newMetadataForm'));
        
        return $form;
    }

    public function handleListFolderRights() {
        $metadataId = $this->httpRequest->query('metadataId');
        if($metadataId === null) {
            throw new RequiredAttributeIsNotSetException('metadataId');
        }

        $links = [
            $this->createBackUrl('list'),
            LinkBuilder::createSimpleLink('Add folder', $this->createURL('newFolderRightForm', ['metadataId' => $metadataId]), 'link')
        ];

        $this->saveToPresenterCache('links', implode('&nbsp;&nbsp;', $links));
    }

    public function renderListFolderRights() {
        $this->template->links = $this->loadFromPresenterCache('links');
    }

    protected function createComponentMetadataFolderRightsGrid(HttpRequest $request) {
        $grid = $this->componentFactory->getGridBuilder($this->containerId);

        $qb = $this->metadataRepository->composeQueryForMetadataFolderRights();
        $qb->andWhere('customMetadataId = ?', [$request->query['metadataId']]);

        $grid->createDataSourceFromQueryBuilder($qb, 'relationId');

        $col = $grid->addColumnText('folderId', 'Folder');
        $col->onRenderColumn[] = function(DatabaseRow $row, Row $_row, Cell $cell, HTML $html, mixed $value) {
            $title = null;

            try {
                $folder = $this->folderManager->getFolderById($value);
                $title = $folder->title;
            } catch(AException) {}

            return $title;
        };

        $remove = $grid->addAction('remove');
        $remove->setTitle('Remove');
        $remove->onCanRender[] = function() {
            return true;
        };
        $remove->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
            $el = HTML::el('a')
                ->text('Remove')
                ->href($this->createURLString('removeFolderRight', ['metadataId' => $row->customMetadataId, 'folderId' => $row->folderId]))
                ->class('grid-link');

            return $el;
        };

        return $grid;
    }

    public function handleRemoveFolderRight() {
        $metadataId = $this->httpRequest->query('metadataId');
        if($metadataId === null) {
            throw new RequiredAttributeIsNotSetException('metadataId');
        }
        $folderId = $this->httpRequest->query('folderId');
        if($folderId === null) {
            throw new RequiredAttributeIsNotSetException('folderId');
        }

        try {
            $this->metadataRepository->beginTransaction(__METHOD__);

            $this->metadataManager->removeMetadataFolderRight($metadataId, $folderId);

            $this->metadataRepository->commit($this->getUserId(), __METHOD__);

            $this->flashMessage('Metadata folder right removed.', 'success');
        } catch(AException $e) {
            $this->metadataRepository->rollback(__METHOD__);

            $this->flashMessage('Could not remove metadata folder right.', 'error', 10);
        }

        $this->redirect($this->createURL('listFolderRights', ['metadataId' => $metadataId]));
    }

    public function handleNewFolderRightForm(?FormRequest $fr = null) {
        if($fr !== null) {
            $metadataId = $this->httpRequest->query('metadataId');
            if($metadataId === null) {
                throw new RequiredAttributeIsNotSetException('metadataId');
            }

            try {
                $this->metadataRepository->beginTransaction(__METHOD__);

                $this->metadataManager->createMetadataFolderRight($metadataId, $fr->folder);

                $this->metadataRepository->commit($this->getUserId(), __METHOD__);

                $this->flashMessage('Folder right added.', 'success');
            } catch(AException $e) {
                $this->metadataRepository->rollback(__METHOD__);

                $this->flashMessage('Could not create folder right. Reason: ' . $e->getMessage(), 'error', 10);
            }

            $this->redirect($this->createURL('listFolderRights', ['metadataId' => $metadataId]));
        } else {
            $foldersDb = $this->metadataManager->getFoldersWithoutMetadataRights($this->httpRequest->query('metadataId'));

            if(empty($foldersDb)) {
                $this->addScript('alert(\'No folder found.\');');
            }

            $folders = [];
            foreach($foldersDb as $folderId => $folder) {
                $folders[] = [
                    'value' => $folderId,
                    'text' => $folder->title
                ];
            }

            $this->httpRequest->params['folders'] = $folders;
        }
    }

    public function renderNewFolderRightForm() {
        $this->template->links = $this->createBackUrl('listFolderRights', ['metadataId' => $this->httpRequest->query('metadataId')]);
    }

    protected function createComponentNewFolderRightForm(HttpRequest $request) {
        $folders = $request->params['folders'];

        $form = $this->componentFactory->getFormBuilder();

        $form->setAction($this->createURL('newFolderRightForm', ['metadataId' => $request->query['metadataId']]));

        $form->addSelect('folder', 'Folder:')
            ->setRequired()
            ->addRawOptions($folders);

        $form->addSubmit();

        return $form;
    }

    public function handleListEnumValues() {
        $metadataId = $this->httpRequest->query('metadataId');

        $links = [
            $this->createBackUrl('list'),
            LinkBuilder::createSimpleLink('New value', $this->createURL('newEnumValueForm', ['metadataId' => $metadataId]), 'link')
        ];

        $this->saveToPresenterCache('links', implode('&nbsp;&nbsp;', $links));
    }

    public function renderListEnumValues() {
        $this->template->links = $this->loadFromPresenterCache('links');
    }

    protected function createComponentMetadataEnumValuesGrid(HttpRequest $request) {
        $grid = $this->componentFactory->getGridBuilder($this->containerId);

        $qb = $this->metadataRepository->composeQueryMetadataEnumValues($request->query['metadataId']);
        $qb->orderBy('metadataKey');

        $grid->createDataSourceFromQueryBuilder($qb, 'valueId');
        $grid->addQueryDependency('metadataId', $request->query['metadataId']);

        $grid->addColumnText('title', 'Title');

        $edit = $grid->addAction('edit');
        $edit->setTitle('Edit');
        $edit->onCanRender[] = function() {
            return true;
        };
        $edit->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) use ($request) {
            $el = HTML::el('a')
                    ->href($this->createURLString('editEnumValueForm', ['metadataId' => $request->query['metadataId'], 'valueId' => $primaryKey]))
                    ->text('Edit')
                    ->class('grid-link');

            return $el;
        };

        $delete = $grid->addAction('delete');
        $delete->setTitle('Delete');
        $delete->onCanRender[] = function(DatabaseRow $row, Row $_row, Action &$action) use ($request) {
            $result = $this->metadataManager->isMetadataEnumValueUsed($row->valueId, $request->query['metadataId']);

            if($result === true) {
                $action->setTitle('This value is being used.');
                return false;
            } else {
                return true;
            }
        };
        $delete->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) use ($request) {
            $el = HTML::el('a')
                    ->text('Delete')
                    ->class('grid-link')
                    ->href($this->createURLString('deleteEnumValue', ['metadataId' => $request->query['metadataId'], 'valueId' => $primaryKey]));

            return $el;
        };

        return $grid;
    }

    public function handleNewEnumValueForm(?FormRequest $fr = null) {
        $metadataId = $this->httpRequest->query('metadataId');
        if($metadataId === null) {
            throw new RequiredAttributeIsNotSetException('metadataId');
        }

        if($fr !== null) {
            try {
                $this->metadataRepository->beginTransaction(__METHOD__);

                $this->metadataManager->createMetadataEnumValue($metadataId, $fr->title);

                $this->metadataRepository->commit($this->getUserId(), __METHOD__);
                
                $this->flashMessage('New metadata value created.', 'success');
            } catch(AException $e) {
                $this->metadataRepository->rollback(__METHOD__);

                $this->flashMessage('Could not create new metadata value. Reason: ' . $e->getMessage(), 'error', 10);
            }
            
            $this->redirect($this->createURL('listEnumValues', ['metadataId' => $metadataId]));
        }
    }

    public function renderNewEnumValueForm() {
        $this->template->links = $this->createBackUrl('listEnumValues', ['metadataId' => $this->httpRequest->query('metadataId')]);
    }

    protected function createComponentNewMetadataEnumValueForm(HttpRequest $request) {
        $form = $this->componentFactory->getFormBuilder();

        $form->setAction($this->createURL('newEnumValueForm', ['metadataId' => $request->query['metadataId']]));

        $form->addTextInput('title', 'Title:')
            ->setRequired();

        $form->addSubmit('Create');

        return $form;
    }

    public function handleEditEnumValueForm(?FormRequest $fr = null) {
        $metadataId = $this->httpRequest->query('metadataId');
        if($metadataId === null) {
            throw new RequiredAttributeIsNotSetException('metadataId');
        }
        $valueId = $this->httpRequest->query('valueId');
        if($valueId === null) {
            throw new RequiredAttributeIsNotSetException('valueId');
        }

        if($fr !== null) {
            try {
                $this->metadataRepository->beginTransaction(__METHOD__);

                $this->metadataManager->updateMetadataEnumValue($valueId, ['title' => $fr->title]);

                $this->metadataRepository->commit($this->getUserId(), __METHOD__);
                
                $this->flashMessage('Metadata value edited.', 'success');
            } catch(AException $e) {
                $this->metadataRepository->rollback(__METHOD__);

                $this->flashMessage('Could not edit metadata value. Reason: ' . $e->getMessage(), 'error', 10);
            }
            
            $this->redirect($this->createURL('listEnumValues', ['metadataId' => $metadataId]));
        } else {
            $valueRow = $this->metadataRepository->getMetadataEnumValueById($valueId);

            $this->httpRequest->params['valueRow'] = DatabaseRow::createFromDbRow($valueRow);
        }
    }

    public function renderEditEnumValueForm() {
        $this->template->links = $this->createBackUrl('listEnumValues', ['metadataId' => $this->httpRequest->query('metadataId')]);
    }

    protected function createComponentEditMetadataEnumValueForm(HttpRequest $request) {
        $form = $this->componentFactory->getFormBuilder();

        $form->setAction($this->createURL('editEnumValueForm', ['metadataId' => $request->query['metadataId'], 'valueId' => $request->query['valueId']]));

        $form->addTextInput('title', 'Title:')
            ->setRequired()
            ->setValue($request->params['valueRow']->title);

        $form->addSubmit('Create');

        return $form;
    }

    public function handleDeleteEnumValue() {
        $metadataId = $this->httpRequest->query('metadataId');
        if($metadataId === null) {
            throw new RequiredAttributeIsNotSetException('metadataId');
        }
        $valueId = $this->httpRequest->query('valueId');
        if($valueId === null) {
            throw new RequiredAttributeIsNotSetException('valueId');
        }

        try {
            $this->metadataRepository->beginTransaction(__METHOD__);

            $this->metadataManager->deleteMetadataEnumValue($valueId);

            $this->metadataRepository->commit($this->getUserId(), __METHOD__);

            $this->flashMessage('Metadata enum value deleted.', 'success');
        } catch(AException $e) {
            $this->metadataRepository->rollback(__METHOD__);

            $this->flashMessage('Could not delete metadata enum value. Reason: ' . $e->getMessage(), 'error', 10);
        }

        $this->redirect($this->createURL('listEnumValues', ['metadataId' => $metadataId]));
    }
}

?>