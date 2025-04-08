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

    /**
     * Returns current version
     */
    public static function getCurrentVersion(): string {
        $releaseDate = (APP_VERSION_RELEASE_DATE != '-' ? ('_' . APP_VERSION_RELEASE_DATE) : '');
        if(APP_BRANCH == 'TEST') {
            return APP_VERSION . '+Build_' . APP_VERSION_BUILD . $releaseDate . '+Branch_' . APP_BRANCH;
        } else {
            return APP_VERSION . ' (' . APP_VERSION . '+Build_' . APP_VERSION_BUILD . $releaseDate . '+Branch_' . APP_BRANCH . ')';
        }
    }
}

?>