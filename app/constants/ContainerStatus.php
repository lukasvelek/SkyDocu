<?php

namespace App\Constants;

class ContainerStatus extends AConstant implements IColorable, IBackgroundColorable {
    public const NEW = 1;
    public const IS_BEING_CREATED = 2;
    public const RUNNING = 3;
    public const NOT_RUNNING = 4;
    public const ERROR_DURING_CREATION = 5;
    public const REQUESTED = 6;
    public const SCHEDULED_FOR_REMOVAL = 7;

    public static function toString(mixed $key): string {
        return match((int)$key) {
            self::NEW => 'New',
            self::IS_BEING_CREATED => 'Being created',
            self::RUNNING => 'Running',
            self::NOT_RUNNING => 'Not running',
            self::ERROR_DURING_CREATION => 'Error during creation',
            self::REQUESTED => 'Requested',
            self::SCHEDULED_FOR_REMOVAL => 'Scheduled for removal'
        };
    }

    public static function getColor($key): ?string {
        return match((int)$key) {
            self::NEW => 'black',
            self::IS_BEING_CREATED => 'orange',
            self::RUNNING => 'green',
            self::NOT_RUNNING => 'red',
            self::ERROR_DURING_CREATION => 'red',
            self::REQUESTED => 'blue',
            self::SCHEDULED_FOR_REMOVAL => 'red',
            default => 'black',
        };
    }

    public static function getBackgroundColor($key): ?string {
        return match((int)$key) {
            self::NEW => 'lightgrey',
            self::IS_BEING_CREATED => 'yellow',
            self::RUNNING => 'lightgreen',
            self::NOT_RUNNING => 'pink',
            self::ERROR_DURING_CREATION => 'pink',
            self::REQUESTED => 'lightblue',
            self::SCHEDULED_FOR_REMOVAL => 'pink',
            default => null,
        };
    }
}

?>