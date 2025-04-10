<?php

namespace App\Schemas\Containers;

use PeeQL\Schema\AQuerySchema;

/**
 * This class represents container process schema
 * 
 * @author Lukas Velek
 */
class GetContainerProcessSchema extends AQuerySchema {
    public function __construct() {
        parent::__construct('GetContainerProcessSchema');
    }

    protected function define() {
        $this->addMultipleColumns([
            'processId',
            'documentId',
            'type',
            'authorUserId',
            'currentOfficerUserId',
            'workflowUserIds',
            'dateCreated',
            'status',
            'currentOfficerSubstituteUserId'
        ]);
    }
}

?>