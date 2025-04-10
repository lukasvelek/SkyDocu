<?php

namespace App\Schemas\Containers;

use PeeQL\Schema\AQuerySchema;

/**
 * This class represents transaction log schema
 * 
 * @author Lukas Velek
 */
class GetContainerTransactionLogSchema extends AQuerySchema {
    public function __construct() {
        parent::__construct('GetContainerTransactionLogSchema');
    }

    protected function define() {
        $this->addMultipleColumns([
            'transactionId',
            'userId',
            'callingMethod',
            'dateCreated'
        ]);
    }
}

?>