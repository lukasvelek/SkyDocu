<?php

namespace App\Constants;

class SystemGroups extends AConstant {
    public const SUPERADMINISTRATORS = 'superadministrators';
    public const CONTAINER_MANAGERS = 'containerManagers';

    public static function toString(mixed $key): string {
        return match($key) {
            self::SUPERADMINISTRATORS => 'Superadministrators',
            self::CONTAINER_MANAGERS => 'Container managers'
        };
    }
}

?>