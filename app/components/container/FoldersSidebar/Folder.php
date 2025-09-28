<?php

namespace App\Components\FoldersSidebar;

use App\Core\DB\DatabaseRow;

/**
 * Folder entity for folder list
 * 
 * @author Lukas Velek
 */
class Folder {
    public DatabaseRow $row;
    public string $folderId;
    public ?string $parentFolderId;

    /**
     * Class constructor
     * 
     * @param DatabaseRow $row Folder row from database
     */
    public function __construct(DatabaseRow $row) {
        $this->row = $row;
        $this->folderId = $row->folderId;
        $this->parentFolderId = $row->parentFolderId;
    }
}

?>