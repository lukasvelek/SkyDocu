<?php

namespace App\Constants\Container;

use App\Constants\AConstant;

class ProcessesGridSystemMetadata extends AConstant {
    public const TYPE = 'type';
    public const AUTHOR_USER_ID = 'authorUserId';
    public const CURRENT_OFFICER_USER_ID = 'currentOfficerUserId';
    public const WORKFLOW_USER_IDS = 'workflowUserIds';
    public const DATE_CREATED = 'dateCreated';
    public const STATUS = 'status';
    public const CURRENT_OFFICER_SUBSTITUTE_USER_ID = 'currentOfficerSubstituteUserId';

    public static function toString($key): ?string {
        return match($key) {
            self::TYPE => 'Type',
            self::AUTHOR_USER_ID => 'Author',
            self::CURRENT_OFFICER_USER_ID => 'Current officer',
            self::WORKFLOW_USER_IDS => 'Workflow users',
            self::DATE_CREATED => 'Date created',
            self::STATUS => 'Status',
            self::CURRENT_OFFICER_SUBSTITUTE_USER_ID => 'Current officer\'s substitute',
            default => null
        };
    }
}

?>