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

    public static function toString($key): ?string {
        return match((int)$key) {
            self::DELETE_CONTAINER => 'Delete container',
            self::DELETE_CONTAINER_PROCESS_INSTANCE => 'Delete container process instance'
        };
    }
}

?>