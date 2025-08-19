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

        $this->filterableColumns = [
            'fullname',
            'email',
            'isTechnical',
            'superiorUserId',
            'isDeleted',
            'orgPosition',
            'orgDepartment',
            'orgSection',
            'personalNumber'
        ];

        $this->sortableColumns = [
            'orgDepartment',
            'orgSection',
            'fullname'
        ];
    }
}

?>