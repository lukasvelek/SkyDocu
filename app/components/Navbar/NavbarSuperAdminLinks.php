<?php

namespace App\Components\Navbar;

/**
 * NavbarSuperAdminLinks contains all navbar links for the SuperAdminModule
 * 
 * @author Lukas Velek
 */
class NavbarSuperAdminLinks {
    public const HOME = ['page' => 'SuperAdmin:Home', 'action' => 'home'];
    public const CONTAINERS = ['page' => 'SuperAdmin:Containers', 'action' => 'list'];
    public const SETTINGS = ['page' => 'SuperAdminSettings:Home', 'action' => 'dashboard'];
    public const USER_PROFILE = ['page' => 'SuperAdmin:User', 'action' => 'profile'];
    public const USER_LOGOUT = ['page' => 'SuperAdmin:Logout', 'action' => 'logout'];

    /**
     * Returns links array where the key is the title and the value is the URL array
     * 
     * @return array<string, array> Links array
     */
    public static function toArray() {
        return [
            'Home' => self::HOME,
            'Containers' => self::CONTAINERS,
            'Settings' => self::SETTINGS
        ];
    }
}

?>