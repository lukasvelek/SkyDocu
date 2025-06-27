<?php

namespace App\Managers;

use App\Constants\SystemGroups;
use App\Core\Caching\CacheNames;
use App\Core\DB\DatabaseRow;
use App\Exceptions\AException;
use App\Exceptions\GeneralException;
use App\Exceptions\NonExistingEntityException;
use App\Logger\Logger;
use App\Repositories\GroupMembershipRepository;
use App\Repositories\GroupRepository;

class GroupManager extends AManager {
    private GroupRepository $groupRepository;
    private GroupMembershipRepository $groupMembershipRepository;

    public function __construct(Logger $logger, EntityManager $entityManager, GroupRepository $groupRepository, GroupMembershipRepository $groupMembershipRepository) {
        parent::__construct($logger, $entityManager);

        $this->groupRepository = $groupRepository;
        $this->groupMembershipRepository = $groupMembershipRepository;
    }

    public function isUserMemberOfSuperadministrators(string $userId) {
        $group = $this->getGroupByTitle(SystemGroups::SUPERADMINISTRATORS);

        $members = $this->groupMembershipRepository->getMemberUserIdsForGroupId($group->groupId);

        return in_array($userId, $members);
    }

    public function isUserMemberOfContainerManagers(string $userId) {
        $group = $this->getGroupByTitle(SystemGroups::CONTAINER_MANAGERS);
        
        $members = $this->groupMembershipRepository->getMemberUserIdsForGroupId($group->groupId);

        return in_array($userId, $members);
    }

    public function createNewGroup(string $title, array $userIdsToAdd = [], ?string $containerId = null) {
        $groupId = $this->createId(EntityManager::GROUPS);

        if(!$this->groupRepository->createNewGroup($groupId, $title, $containerId)) {
            throw new GeneralException('Could not create group.');
        }

        if(!empty($userIdsToAdd)) {
            foreach($userIdsToAdd as $userId) {
                try {
                    $groupUserId = $this->createId(EntityManager::GROUP_USERS);

                    if(!$this->groupMembershipRepository->addUserToGroup($groupUserId, $groupId, $userId)) {
                        throw new GeneralException('Could not add user to group.');
                    }
                } catch(AException $e) {
                    continue;
                }
            }
        }

        if(!$this->cacheFactory->invalidateCacheByNamespace(CacheNames::GROUPS)) {
            throw new GeneralException('Could not invalidate cache.');
        }
    }

    public function getGroupById(string $groupId) {
        $group = $this->groupRepository->getGroupById($groupId);

        if($group === null) {
            throw new NonExistingEntityException('Group does not exist.');
        }

        return DatabaseRow::createFromDbRow($group);
    }

    public function getGroupUsersForGroupId(string $groupId) {
        return $this->groupMembershipRepository->getGroupUsersForGroupId($groupId);
    }

    public function addUserToGroup(string $userId, string $groupId) {
        $groupUserId = $this->createId(EntityManager::GROUP_USERS);

        if(!$this->groupMembershipRepository->addUserToGroup($groupUserId, $groupId, $userId)) {
            throw new GeneralException('User is probably member of the group.');
        }

        if(!$this->cacheFactory->invalidateCacheByNamespace(CacheNames::GROUP_MEMBERSHIPS) ||
           !$this->cacheFactory->invalidateCacheByNamespace(CacheNames::USER_GROUP_MEMBERSHIPS) ||
           !$this->cacheFactory->invalidateCacheByNamespace(CacheNames::VISIBLE_FOLDERS_FOR_USER) ||
           !$this->cacheFactory->invalidateCacheByNamespace(CacheNames::USER_GROUP_MEMBERSHIPS)) {
            throw new GeneralException('Could not invalidate cache.');
        }
    }

    public function removeUserFromGroup(string $userId, string $groupId) {
        if(!$this->groupMembershipRepository->removeUserFromGroup($groupId, $userId)) {
            throw new GeneralException('User is not member of the group.');
        }

        if(!$this->cacheFactory->invalidateCacheByNamespace(CacheNames::GROUP_MEMBERSHIPS) ||
            !$this->cacheFactory->invalidateCacheByNamespace(CacheNames::USER_GROUP_MEMBERSHIPS) ||
            !$this->cacheFactory->invalidateCacheByNamespace(CacheNames::VISIBLE_FOLDERS_FOR_USER) ||
            !$this->cacheFactory->invalidateCacheByNamespace(CacheNames::USER_GROUP_MEMBERSHIPS)) {
            throw new GeneralException('Could not invalidate cache.');
        }
    }

    public function getGroupByTitle(string $title) {
        $group = $this->groupRepository->getGroupByTitle($title);

        if($group === null) {
            throw new NonExistingEntityException('Group does not exist.');
        }

        return DatabaseRow::createFromDbRow($group);
    }

    /**
     * Returns an array of user IDs that are members of a group with a title
     * 
     * @param string $title Group title
     */
    public function getGroupUsersForGroupTitle(string $title): array {
        $group = $this->getGroupByTitle($title);

        return $this->getGroupUsersForGroupId($group->groupId);
    }

    public function getMembershipsForUser(string $userId, bool $force = false) {
        $groupIds = $this->groupRepository->getGroupsForUser($userId, $force);

        $groups = [];
        foreach($groupIds as $id) {
            $groups[] = $this->getGroupById($id);
        }

        return $groups;
    }

    public function removeAllUsersFromGroup(string $groupId, array &$exceptions) {
        $userIds = $this->getGroupUsersForGroupId($groupId);

        $removed = 0;
        foreach($userIds as $userId) {
            try {
                $this->removeUserFromGroup($userId, $groupId);
                $removed++;
            } catch(AException $e) {
                $exceptions[$userId] = $e;

                continue;
            }
        }

        if($removed < count($userIds)) {
            return false;
        } else {
            return true;
        }
    }

    public function removeGroup(string $groupId) {
        if(!$this->groupRepository->removeGroup($groupId)) {
            throw new GeneralException('Could not remove group.');
        }
    }

    public function getAllContainerGroups() {
        $qb = $this->groupRepository->composeQueryForGroups();
        $qb->andWhere('containerId IS NOT NULL')
            ->execute();

        $groups = [];
        while($row = $qb->fetchAssoc()) {
            $groups[] = $row['groupId'];
        }

        return $groups;
    }

    public function getAllContainersOnlyUsers() {
        $groups = $this->getAllContainerGroups();

        $superAdministrators = $this->getAllSuperadministrators();
        $users = [];
        foreach($groups as $groupId) {
            $members = $this->getGroupUsersForGroupId($groupId);

            foreach($members as $memberId) {
                if(!in_array($memberId, $superAdministrators)) {
                    $users[] = $memberId;
                }
            }
        }

        return $users;
    }

    public function getAllSuperadministrators() {
        $users = $this->getGroupUsersForGroupTitle('superadministrators');

        return $users;
    }
}

?>