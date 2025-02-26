<?php

namespace App\Core;

/**
 * Helps with App configuration
 * 
 * @author Lukas Velek
 */
class Configuration {
    /**
     * Returns the Application instance branch
     * 
     * If APP_BRANCH define contains a different value than TEST or PROD, then PROD is implicitly returned.
     */
    public static function getAppBranch(): string {
        if(!in_array(APP_BRANCH, ['PROD', 'TEST'])) {
            return 'PROD';
        }

        return APP_BRANCH;
    }
}

?>