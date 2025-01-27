<?php

namespace App\Components\Navbar;

/**
 * NavbarSuperAdminSettingsLinks contains all navbar links for the SuperAdminSettingsModule
 * 
 * @author Lukas Velek
 */
class NavbarSuperAdminSettingsLinks {
    public const LEAVE_SETTINGS = ['page' => 'SuperAdmin:Home', 'action' => 'home'];
    public const DASHBOARD = ['page' => 'SuperAdminSettings:Home', 'action' => 'dashboard'];
    public const USERS = ['page' => 'SuperAdminSettings:Users', 'action' => 'list'];
    public const GROUPS = ['page' => 'SuperAdminSettings:Groups', 'action' => 'list'];
    public const BG_SERVICES = ['page' => 'SuperAdminSettings:BackgroundServices', 'action' => 'list'];
    public const ABOUT_APP = ['page' => 'SuperAdminSettings:AboutApplication', 'action' => 'default'];

    public const USER_PROFILE = ['page' => 'SuperAdmin:UserProfile', 'action' => 'profile'];
    public const USER_LOGOUT = ['page' => 'SuperAdmin:Logout', 'action' => 'logout'];

    /**
     * Returns links array where the key is the title and the value is the URL array
     * 
     * @return array<string, array> Links array
     */
    public static function toArray() {
        return [
            'Leave settings' => self::LEAVE_SETTINGS,
            'Dashboard' => self::DASHBOARD,
            'Users' => self::USERS,
            'Groups' => self::GROUPS,
            'Background services' => self::BG_SERVICES,
            'About application' => self::ABOUT_APP
        ];
    }
}

?>