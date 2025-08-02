<?php

namespace App\Schemas\Containers;

use PeeQL\Schema\AQuerySchema;

/**
 * This class represents container process instance schema
 * 
 * @author Lukas Velek
 */
class GetContainerProcessInstanceSchema extends AQuerySchema {
    public function __construct() {
        parent::__construct('GetContainerProcessInstanceSchema');
    }

    protected function define() {
        $this->addMultipleColumns([
            'instanceId',
            'processId',
            'userId',
            'data',
            'currentOfficerId',
            'currentOfficerType',
            'status',
            'dateCreated',
            'dateModified',
            'description',
            'systemStatus'
        ]);
    }
}

?>