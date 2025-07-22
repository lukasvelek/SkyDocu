<?php

namespace App\Schemas;

use PeeQL\Schema\AQuerySchema;

/**
 * This class represents users schema
 * 
 * @author Lukas Velek
 */
class GetUsersSchema extends AQuerySchema {
    public function __construct() {
        parent::__construct('GetUsersSchema');
    }

    protected function define() {
        $this->addMultipleColumns([
            'userId',
            'username',
            'fullname',
            'dateCreated',
            'email',
            'isTechnical',
            'appDesignTheme',
            'isDeleted',
            'dateFormat',
            'timeFormat',
            'superiorUserId',
            'orgPosition',
            'orgDepartment',
            'orgSection',
            'personalNumber'
        ]);
    }
}

?>