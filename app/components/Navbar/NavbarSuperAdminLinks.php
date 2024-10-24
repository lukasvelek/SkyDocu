<?php

namespace App\Components\Navbar;

class NavbarSuperAdminLinks {
    public const HOME = ['page' => 'SuperAdmin:Home', 'action' => 'home'];
    public const CONTAINERS = ['page' => 'SuperAdmin:Containers', 'action' => 'list'];
    public const USER_PROFILE = ['page' => 'SuperAdmin:UserProfile', 'action' => 'profile'];
    public const USER_LOGOUT = ['page' => 'SuperAdmin:Logout', 'action' => 'logout'];

    public static function toArray() {
        return [
            'Home' => self::HOME,
            'Containers' => self::CONTAINERS
        ];
    }
}

?>