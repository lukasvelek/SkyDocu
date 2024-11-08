<?php

namespace App\Modules\AdminModule;

use App\Components\Sidebar\Sidebar;
use App\Core\DB\DatabaseRow;
use App\Core\Http\HttpRequest;
use App\Modules\TemplateObject;
use App\UI\FormBuilder2\FormBuilder2;
use App\UI\GridBuilder2\Row;
use App\UI\HTML\HTML;
use App\UI\LinkBuilder;

class DocumentFoldersPresenter extends AAdminPresenter {
    private Sidebar $sidebar;

    public function __construct() {
        parent::__construct('DocumentFoldersPresenter', 'Document folders');
    }

    public function startup() {
        parent::startup();

        $this->sidebar = new Sidebar();

        $this->sidebar->addLink('Dashboard', $this->createFullURL('Admin:Documents', 'dashboard'), false);
        $this->sidebar->addLink('Folders', $this->createFullURL('Admin:DocumentFolders', 'list'), true);
        $this->sidebar->addLink('Metadata', $this->createFullURL('Admin:DocumentMetadata', 'list'), false);

        $this->addBeforeRenderCallback(function(TemplateObject &$template) {
            $template->sidebar = $this->sidebar;
        });
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

    public function renderNewFolderForm() {
        $this->template->links = [
            LinkBuilder::createSimpleLink('&larr; Back', $this->createURL('list'), 'link')
        ];
    }

    protected function createComponentNewDocumentFolderForm(HttpRequest $request) {
        $form = new FormBuilder2($request);

        $form->setAction($this->createURL('processNewDocumentForm'));

        $form->addTextInput('title', 'Title:')
            ->setRequired();

        $form->addSubmit('Create');

        return $form;
    }
}

?>