<?php

namespace App\Schemas\Containers;

use PeeQL\Schema\AQuerySchema;

/**
 * This class represents container documents schema
 * 
 * @author Lukas Velek
 */
class GetContainerDocumentsSchema extends AQuerySchema {
    public function __construct() {
        parent::__construct('GetContainerDocumentsSchema');
    }

    protected function define() {
        $this->addMultipleColumns([
            'documentId',
            'title',
            'authorUserId',
            'description',
            'status',
            'classId',
            'folderId',
            'dateCreated'
        ]);
    }
}

?>