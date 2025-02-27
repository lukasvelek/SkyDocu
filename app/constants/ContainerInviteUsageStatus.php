<?php

namespace App\Constants;

class ContainerInviteUsageStatus extends AConstant implements IColorable, IBackgroundColorable {
    public const NEW = 1;
    public const ACCEPTED = 2;
    public const REJECTED = 3;

    public static function toString($key): ?string {
        return match((int)$key) {
            self::NEW => 'New',
            self::ACCEPTED => 'Accepted',
            self::REJECTED => 'Rejected'
        };
    }

    public static function getColor($key): ?string {
        return match((int)$key) {
            self::NEW => 'blue',
            self::ACCEPTED => 'green',
            self::REJECTED => 'red',
            default => 'black'
        };
    }

    public static function getBackgroundColor($key): ?string {
        return match((int)$key) {
            self::NEW => 'lightblue',
            self::ACCEPTED => 'lightgreen',
            self::REJECTED => 'pink',
            default => null
        };
    }
}

?>