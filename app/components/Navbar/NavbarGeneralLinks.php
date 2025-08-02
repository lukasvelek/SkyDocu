<?php

namespace App\Components\Navbar;

/**
 * NavbarGeneralLinks contains all navbar links for the UserModule
 * 
 * @author Lukas Velek
 */
class NavbarGeneralLinks {
    public const DASHBOARD = ['page' => 'User:Home', 'action' => 'dashboard'];
    public const DOCUMENTS = ['page' => 'User:Documents', 'action' => 'list'];
    public const ARCHIVE = ['page' => 'User:Archive', 'action' => 'list'];
    public const PROCESSES = ['page' => 'User:Processes', 'action' => 'list', 'view' => 'waitingForMe'];
    //public const REPORTS = ['page' => 'User:Reports', 'action' => 'list'];
    public const CONTACTS = ['page' => 'User:Contacts', 'action' => 'contactsGrid'];

    public const A_SETTINGS = ['page' => 'Admin:Home', 'action' => 'dashboard'];

    public const USER_PROFILE = ['page' => 'User:User', 'action' => 'profile'];
    public const USER_LOGOUT = ['page' => 'User:Logout', 'action' => 'logout'];

    /**
     * Returns links array where the key is the title and the value is the URL array
     * 
     * @return array<string, array> Links array
     */
    public static function toArray() {
        return [
            'Dashboard' => self::DASHBOARD,
            'Documents' => self::DOCUMENTS,
            'Archive' => self::ARCHIVE,
            'Processes' => self::PROCESSES,
            //'Reports' => self::REPORTS,
            'Contacts' => self::CONTACTS
        ];
    }
}

?>