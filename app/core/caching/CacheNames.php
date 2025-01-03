<?php

namespace App\Core\Caching;

use ReflectionClass;

/**
 * CacheNames contains the list of all cache namespaces used
 * 
 * @author Lukas Velek
 */
class CacheNames {
    public const USERS = 'users';
    public const USERS_USERNAME_TO_ID_MAPPING = 'usersUsernameToIdMapping';
    public const GROUPS = 'groups';
    public const GROUP_MEMBERSHIPS = 'groupMemberships';
    public const GROUP_TITLE_TO_ID_MAPPING = 'groupsTitleToIdMapping';
    public const FLASH_MESSAGES = 'flashMessages';
    public const GRID_EXPORTS = 'gridExports';
    public const GRID_PAGE_DATA = 'gridPageData';
    public const METADATA_VALUES = 'customMetadataValues';
    public const VISIBLE_FOLDERS_FOR_USER = 'visibleFoldersForUser';
    public const VISIBLE_FOLDER_IDS_FOR_GROUP = 'visibleFolderIdsForGroup';
    public const CONTAINERS = 'containers';
    public const GROUP_STANDARD_OPERATIONS_RIGHTS = 'groupStandardOperationsRights';
    public const USER_GROUP_MEMBERSHIPS = 'userGroupMemberships';
    public const NAVBAR_CONTAINER_SWITCH_USER_MEMBERSHIPS = 'navbarContainerSwitchUserMemberships';
    public const PROCESS_TYPES = 'processTypes';
    public const FOLDER_SUBFOLDERS_MAPPING = 'folderSubfoldersMapping';
    public const GRID_FILTER_DATA = 'gridFilterData';

    /**
     * Returns an array with all cache namespaces
     * 
     * @return array<string> Cache namespaces
     */
    public static function getAll() {
        $rc = new ReflectionClass(static::class);
        $constants = $rc->getConstants();

        $result = [];
        foreach($constants as $name => $value) {
            $result[] = $value;
        }

        return $result;
    }
}

?>