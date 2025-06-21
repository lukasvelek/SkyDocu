<?php

namespace App\Schemas;

use PeeQL\Schema\AQuerySchema;

/**
 * This class represents transaction log schema
 * 
 * @author Lukas Velek
 */
class GetTransactionLogSchema extends AQuerySchema {
    public function __construct() {
        parent::__construct('GetTransactionLogSchema');
    }

    protected function define() {
        $this->addMultipleColumns([
            'transactionId',
            'userId',
            'callingMethod',
            'dateCreated',
            'containerId'
        ]);

        $this->addRequiredFilterColumn('containerId');
    }
}

?>