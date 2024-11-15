<?php

namespace App\Components\Navbar;

class NavbarAdminLinks {
    public const LEAVE_ADMINISTRATION = ['page' => 'User:Home', 'action' => 'dashboard'];
    public const DASHBOARD = ['page' => 'Admin:Home', 'action' => 'dashboard'];
    public const MEMBERS = ['page' => 'Admin:Members', 'action' => 'dashboard'];
    public const DOCUMENTS = ['page' => 'Admin:Documents', 'action' => 'dashboard'];
    public const SYSTEM = ['page' => 'Admin:System', 'action' => 'dashboard'];

    public static function toArray() {
        return [
            'Leave administration' => self::LEAVE_ADMINISTRATION,
            'Dashboard' => self::DASHBOARD,
            'Members' => self::MEMBERS,
            'Documents' => self::DOCUMENTS,
            'System' => self::SYSTEM
        ];
    }
}

?>