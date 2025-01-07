<?php

namespace App\Components\Navbar;

/**
 * NavbarAdminLinks contains all navbar links for the AdminModule
 * 
 * @author Lukas Velek
 */
class NavbarAdminLinks {
    public const LEAVE_ADMINISTRATION = ['page' => 'User:Home', 'action' => 'dashboard'];
    public const DASHBOARD = ['page' => 'Admin:Home', 'action' => 'dashboard'];
    public const MEMBERS = ['page' => 'Admin:Members', 'action' => 'dashboard'];
    public const DOCUMENTS = ['page' => 'Admin:Documents', 'action' => 'dashboard'];
    public const PROCESSES = ['page' => 'Admin:Processes', 'action' => 'dashboard'];
    public const SYSTEM = ['page' => 'Admin:System', 'action' => 'dashboard'];

    /**
     * Returns links array where the key is the title and the value is the URL array
     * 
     * @return array<string, array> Links array
     */
    public static function toArray() {
        return [
            'Leave administration' => self::LEAVE_ADMINISTRATION,
            'Dashboard' => self::DASHBOARD,
            'Members' => self::MEMBERS,
            'Documents' => self::DOCUMENTS,
            'Processes' => self::PROCESSES,
            'System' => self::SYSTEM
        ];
    }
}

?>