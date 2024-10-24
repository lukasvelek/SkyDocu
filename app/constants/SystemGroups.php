<?php

namespace App\Constants;

class SystemGroups extends AConstant {
    public const SUPERADMINISTRATORS = 'superadministrators';

    public static function getAll(): array {
        return [
            self::SUPERADMINISTRATORS => self::toString(self::SUPERADMINISTRATORS)
        ];
    }

    public static function toString(mixed $key): string {
        return match($key) {
            self::SUPERADMINISTRATORS => 'Superadministrators'
        };
    }
}

?>