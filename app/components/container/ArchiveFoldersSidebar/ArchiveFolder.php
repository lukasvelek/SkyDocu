<?php

namespace App\Components\ArchiveFoldersSidebar;

use App\Core\DB\DatabaseRow;

/**
 * Archive folder entity for archive folder list
 * 
 * @author Lukas Velek
 */
class ArchiveFolder {
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