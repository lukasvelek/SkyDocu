<?php

namespace App\Components\Navbar;

class NavbarAdminLinks {
    public const LEAVE_ADMINISTRATION = ['page' => 'User:Home', 'action' => 'dashboard'];
    public const DASHBOARD = ['page' => 'Admin:Home', 'action' => 'dashboard'];
    public const MEMBERS = ['page' => 'Admin:Members', 'action' => 'dashboard'];

    public static function toArray() {
        return [
            'Leave administration' => self::LEAVE_ADMINISTRATION,
            'Dashboard' => self::DASHBOARD,
            'Members' => self::MEMBERS
        ];
    }
}

?>