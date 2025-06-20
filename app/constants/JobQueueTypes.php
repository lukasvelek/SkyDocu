<?php

namespace App\Constants;

class JobQueueTypes extends AConstant {
    /**
     * Delete a container
     */
    public const DELETE_CONTAINER = 1;
    /**
     * Delete a process instance in a container
     */
    public const DELETE_CONTAINER_PROCESS_INSTANCE = 2;
    /**
     * Publish a process version to distribution
     */
    public const PUBLISH_PROCESS_VERSION_TO_DISTRIBUTION = 3;
    /**
     * Change process visibility in distribution
     */
    public const CHANGE_PROCESS_VISIBILITY_IN_DISTRIBUTION = 4;

    public static function toString($key): ?string {
        return match((int)$key) {
            self::DELETE_CONTAINER => 'Delete container',
            self::DELETE_CONTAINER_PROCESS_INSTANCE => 'Delete container process instance',
            self::PUBLISH_PROCESS_VERSION_TO_DISTRIBUTION => 'Publish process version to distribution',
            self::CHANGE_PROCESS_VISIBILITY_IN_DISTRIBUTION => 'Change process visibility in distribution'
        };
    }
}

?>