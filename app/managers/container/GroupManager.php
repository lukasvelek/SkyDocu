<?php

namespace App\Managers\Container;

use App\Core\DB\DatabaseRow;
use App\Exceptions\GeneralException;
use App\Logger\Logger;
use App\Managers\AManager;
use App\Managers\EntityManager;
use App\Repositories\Container\GroupRepository;
use App\Repositories\UserRepository;

class GroupManager extends AManager {
    public const ALL_USERS = 'All users';
    public const ADMINISTRATORS = 'Administrators';

    private GroupRepository $gr;
    private UserRepository $ur;

    public function __construct(Logger $logger, EntityManager $em, GroupRepository $gr, UserRepository $ur) {
        parent::__construct($logger, $em);

        $this->gr = $gr;
        $this->ur = $ur;
    }

    public function addUserToGroupTitle(string $groupTitle, string $userId) {
        $group = $this->getGroupByTitle($groupTitle);

        $this->addUserToGroupId($group->groupId, $userId);
    }

    public function addUserToGroupId(string $groupId, string $userId) {
        $relationId = $this->createId(EntityManager::C_GROUP_USERS_RELATION);

        if(!$this->gr->addUserToGroup($relationId, $groupId, $userId)) {
            throw new GeneralException('Database error.');
        }
    }

    public function getGroupByTitle(string $groupTitle) {
        $result = $this->gr->getGroupByTitle($groupTitle);

        if($result === null){
            throw new GeneralException('Group does not exist.');
        }

        $result = DatabaseRow::createFromDbRow($result);

        return $result;
    }

    public function getGroupById(string $groupId) {
        $result = $this->gr->getGroupById($groupId);

        if($result === null) {
            throw new GeneralException('Group does not exist.');
        }

        $result = DatabaseRow::createFromDbRow($result);

        return $result;
    }
}

?>