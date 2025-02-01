<?php

namespace App\Modules\UserModule;

use App\Components\ArchiveFoldersSidebar\ArchiveFoldersSidebar;
use App\Components\DocumentsGrid\DocumentsGrid;
use App\Constants\Container\GridNames;
use App\Core\Http\HttpRequest;

class ArchivePresenter extends AUserPresenter {
    private ?string $currentFolderId;

    public function __construct() {
        parent::__construct('ArchivePresenter', 'Archive');
    }

    public function startup() {
        parent::startup();

        $this->currentFolderId = $this->httpSessionGet('current_archive_folder_id');
    }

    public function renderList() {}

    protected function createComponentFoldersSidebar(HttpRequest $request) {
        $sidebar = new ArchiveFoldersSidebar($request, $this->archiveManager, 'list');

        return $sidebar;
    }
}

?>