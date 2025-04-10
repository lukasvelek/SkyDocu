<?php

namespace App\Schemas;

use PeeQL\Schema\AQuerySchema;

/**
 * This class represents groups schema
 * 
 * @author Lukas Velek
 */
class GetGroupsSchema extends AQuerySchema {
    public function __construct() {
        parent::__construct('GetGroupsSchema');
    }

    protected function define() {
        $this->addMultipleColumns([
            'groupId',
            'title',
            'containerId',
            'dateCreated'
        ]);
    }
}

?>