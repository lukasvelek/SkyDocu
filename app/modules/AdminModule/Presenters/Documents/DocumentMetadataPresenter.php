<?php

namespace App\Modules\AdminModule;

use App\Components\DocumentMetadataForm\DocumentMetadataForm;
use App\Constants\Container\CustomMetadataTypes;
use App\Core\AjaxRequestBuilder;
use App\Core\DB\DatabaseRow;
use App\Core\Http\HttpRequest;
use App\Exceptions\AException;
use App\UI\FormBuilder2\FormBuilder2;
use App\UI\FormBuilder\FormResponse;
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
        $grid = $this->getGridBuilder();

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

    public function handleNewMetadataForm(?FormResponse $fr = null) {
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
}

?>