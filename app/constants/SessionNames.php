<?php

namespace App\Constants;

/**
 * SessionNames contains names of all session variables used in the application
 * 
 * @author Lukas Velek
 */
class SessionNames {
    public const USER_ID = 'user_id';
    public const USERNAME = 'username';
    public const FULLNAME = 'fullname';
    public const LOGIN_HASH = 'login_hash';
    public const IS_LOGGING_IN = 'is_logging_in';
    public const IS_REGISTERING = 'is_registering';
    public const CONTAINER = 'container';
    public const IS_CHOOSING_CONTAINER = 'is_choosing_container';
    public const CURRENT_ARCHIVE_FOLDER_ID = 'current_archive_folder_id';
    public const CURRENT_DOCUMENT_FOLDER_ID = 'current_document_folder_id';
    public const FLASH_MESSAGES = 'flash_messages';
}

?>