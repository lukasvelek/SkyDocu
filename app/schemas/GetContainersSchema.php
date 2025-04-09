<?php

namespace App\Schemas;

use PeeQL\Schema\AQuerySchema;

class GetContainersSchema extends AQuerySchema {
    public function __construct() {
        parent::__construct('GetContainersSchema');
    }

    protected function define() {
        $this->addMultipleColumns([
            'containerId',
            'title',
            'description',
            'status',
            'dateCreated',
            'environment',
            'canShowContainerReferent',
            'isInDistribution',
            'permanentFlashMessage'
        ]);
    }
}

?>