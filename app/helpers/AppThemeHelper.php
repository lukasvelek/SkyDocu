<?php

namespace App\Helpers;

use App\Constants\AppDesignThemes;
use App\Core\Application;

/**
 * AppThemeHelper helps with application themes
 * 
 * @author Lukas Velek
 */
class AppThemeHelper {
    /**
     * Returns current user's application theme. If $app is null or current user is not set then AppDesignThemes::LIGHT is returned.
     * 
     * @param ?Application $app Application instance
     */
    public static function getAppThemeForUser(?Application $app): int {
        if($app !== null &&
           $app->currentUser !== null) {
            return $app->currentUser->getAppDesignTheme();
        }

        return AppDesignThemes::LIGHT;
    }
}

?>