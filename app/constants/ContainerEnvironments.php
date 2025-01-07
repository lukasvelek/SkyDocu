<?php

namespace App\Constants;

class ContainerEnvironments extends AConstant {
    //public const DEV = 1;
    public const TEST = 2;
    public const PROD = 3;

    public static function toString($key): string {
        return match((int)$key) {
            //self::DEV => 'Dev',
            self::TEST => 'Test',
            self::PROD => 'Prod'
        };
    }
}

?>