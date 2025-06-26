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
        $fullVersion = APP_VERSION . '+Build_' . APP_VERSION_BUILD . $releaseDate . '+Commit_' . APP_VERSION_GITHUB_COMMIT;

        if(APP_BRANCH == 'PROD') {
            $fullVersion = APP_VERSION . ' ' . $fullVersion;
        }

        return $fullVersion;
    }
}

?>