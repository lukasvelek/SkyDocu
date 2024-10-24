<?php

namespace App\Managers;

use App\Core\HashManager;
use App\Logger\Logger;
use App\Repositories\ContentRepository;

/**
 * EntityManager contains useful methods for working with entities saved to database
 * 
 * @author Lukas Velek
 */
class EntityManager extends AManager {
    public const USERS = 'users';
    public const TRANSACTIONS = 'transaction_log';
    public const GRID_EXPORTS = 'grid_exports';
    public const GROUPS = 'groups';
    public const GROUP_USERS = 'group_users';

    private const __MAX__ = 100;

    private ContentRepository $cr;

    /**
     * Class constructor
     * 
     * @param Logger $logger Logger instance
     * @param ContentRepository $cr ContentRepository instance
     */
    public function __construct(Logger $logger, ContentRepository $cr) {
        parent::__construct($logger, null);

        $this->cr = $cr;
    }

    /**
     * Generates unique entity ID for given category
     * 
     * @param string $category (see constants in \App\Managers\EntityManager)
     * @return null|string Generated unique entity ID or null
     */
    public function generateEntityId(string $category) {
        $unique = true;
        $run = true;

        $entityId = null;
        $x = 0;
        while($run) {
            $entityId = HashManager::createEntityId();

            $primaryKeyName = $this->getPrimaryKeyNameByCategory($category);

            $unique = $this->cr->checkIdIsUnique($category, $primaryKeyName, $entityId);

            if($unique || $x >= self::__MAX__) {
                $run = false;
                break;
            }

            $x++;
        }

        return $entityId;
    }

    /**
     * Returns primary key for given category (database table)
     * 
     * @return string Primary key
     */
    private function getPrimaryKeyNameByCategory(string $category) {
        return match($category) {
            self::USERS => 'userId',
            self::TRANSACTIONS => 'transactionId',
            self::GRID_EXPORTS => 'exportId',
            self::GROUPS => 'groupId',
            self::GROUP_USERS => 'groupUserId'
        };
    }
}

?>