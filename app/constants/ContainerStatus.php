<?php

namespace App\Constants;

class ContainerStatus extends AConstant {
    public const NEW = 1;
    public const IS_BEING_CREATED = 2;
    public const RUNNING = 3;
    public const NOT_RUNNING = 4;

    public static function getAll(): array {
        return [
            self::NEW => self::toString(self::NEW),
            self::IS_BEING_CREATED => self::toString(self::IS_BEING_CREATED),
            self::RUNNING => self::toString(self::RUNNING),
            self::NOT_RUNNING => self::toString(self::NOT_RUNNING)
        ];
    }

    public static function toString(mixed $key): string {
        return match((int)$key) {
            self::NEW => 'New',
            self::IS_BEING_CREATED => 'Being created',
            self::RUNNING => 'Running',
            self::NOT_RUNNING => 'Not running'
        };
    }
}

?>