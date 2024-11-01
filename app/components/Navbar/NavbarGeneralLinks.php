<?php

namespace App\Components\Navbar;

class NavbarGeneralLinks {
    public const DASHBOARD = ['page' => 'User:Home', 'action' => 'dashboard'];
    public const DOCUMENTS = ['page' => 'User:Documents', 'action' => 'list'];

    public const USER_PROFILE = ['page' => 'User:User', 'action' => 'profile'];
    public const USER_LOGOUT = ['page' => 'User:Logout', 'action' => 'logout'];

    public static function toArray() {
        return [
            'Dashboard' => self::DASHBOARD,
            'Documents' => self::DOCUMENTS
        ];
    }
}

?>