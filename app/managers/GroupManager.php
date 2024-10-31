<?php

namespace App\Managers;

use App\Constants\SystemGroups;
use App\Core\DB\DatabaseRow;
use App\Exceptions\AException;
use App\Exceptions\GeneralException;
use App\Exceptions\NonExistingEntityException;
use App\Logger\Logger;
use App\Repositories\GroupMembershipRepository;
use App\Repositories\GroupRepository;

class GroupManager extends AManager {
    private GroupRepository $gr;
    private GroupMembershipRepository $gmr;

    public function __construct(Logger $logger, EntityManager $em, GroupRepository $gr, GroupMembershipRepository $gmr) {
        parent::__construct($logger, $em);

        $this->gr = $gr;
        $this->gmr = $gmr;
    }

    public function isUserMemberOfSuperadministrators(string $userId) {
        $group = $this->gr->getGroupByTitle(SystemGroups::SUPERADMINISTRATORS);

        $members = $this->gmr->getMemberUserIdsForGroupId($group->getId());

        return in_array($userId, $members);
    }

    public function createNewGroup(string $title, array $userIdsToAdd = [], ?string $containerId = null) {
        $groupId = $this->createId(EntityManager::GROUPS);

        if(!$this->gr->createNewGroup($groupId, $title, $containerId)) {
            throw new GeneralException('Could not create group.');
        }

        if(!empty($userIdsToAdd)) {
            foreach($userIdsToAdd as $userId) {
                try {
                    $groupUserId = $this->createId(EntityManager::GROUP_USERS);

                    if(!$this->gmr->addUserToGroup($groupUserId, $groupId, $userId)) {
                        throw new GeneralException('Could not add user to group.');
                    }
                } catch(AException $e) {
                    continue;
                }
            }
        }
    }

    public function getGroupById(string $groupId) {
        $group = $this->gr->getGroupById($groupId);

        if($group === null) {
            throw new NonExistingEntityException('Group does not exist.');
        }

        return DatabaseRow::createFromDbRow($group);
    }
}

?>