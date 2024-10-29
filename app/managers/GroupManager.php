<?php

namespace App\Managers;

use App\Constants\SystemGroups;
use App\Exceptions\AException;
use App\Exceptions\GeneralException;
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

    public function createNewGroup(string $title, array $userIdsToAdd = []) {
        $groupId = $this->createId(EntityManager::GROUPS);

        if(!$this->gr->createNewGroup($groupId, $title)) {
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
}

?>