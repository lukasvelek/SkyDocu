<?php

namespace App\Managers\Container;

use App\Constants\Container\GroupStandardOperationRights;
use App\Constants\Container\SystemGroups;
use App\Core\Caching\CacheNames;
use App\Core\DB\DatabaseRow;
use App\Exceptions\GeneralException;
use App\Logger\Logger;
use App\Managers\AManager;
use App\Repositories\Container\GroupRepository;
use App\Repositories\UserRepository;

class GroupManager extends AManager {
    public GroupRepository $groupRepository;
    private UserRepository $userRepository;

    public function __construct(Logger $logger, GroupRepository $groupRepository, UserRepository $userRepository) {
        parent::__construct($logger);

        $this->groupRepository = $groupRepository;
        $this->userRepository = $userRepository;
    }

    public function addUserToGroupTitle(string $groupTitle, string $userId) {
        $group = $this->getGroupByTitle($groupTitle);

        $this->addUserToGroupId($group->groupId, $userId);
    }

    public function addUserToGroupId(string $groupId, string $userId) {
        $relationId = $this->createId();

        if(!$this->groupRepository->addUserToGroup($relationId, $groupId, $userId)) {
            throw new GeneralException('Database error.');
        }

        if(!$this->cacheFactory->invalidateCacheByNamespace(CacheNames::GROUP_MEMBERSHIPS) ||
            !$this->cacheFactory->invalidateCacheByNamespace(CacheNames::VISIBLE_FOLDERS_FOR_USER) ||
            !$this->cacheFactory->invalidateCacheByNamespace(CacheNames::USER_GROUP_MEMBERSHIPS)
        ) {
            throw new GeneralException('Could not invalidate cache.');
        }
    }

    public function removeUserFromGroupId(string $groupId, string $userId) {
        if(!$this->groupRepository->removeUserFromGroup($groupId, $userId)) {
            throw new GeneralException('Database error.');
        }

        if(!$this->cacheFactory->invalidateCacheByNamespace(CacheNames::GROUP_MEMBERSHIPS) ||
            !$this->cacheFactory->invalidateCacheByNamespace(CacheNames::VISIBLE_FOLDERS_FOR_USER) ||
            !$this->cacheFactory->invalidateCacheByNamespace(CacheNames::USER_GROUP_MEMBERSHIPS)
        ) {
            throw new GeneralException('Could not invalidate cache.');
        }
    }

    public function getGroupByTitle(string $groupTitle) {
        $result = $this->groupRepository->getGroupByTitle($groupTitle);

        if($result === null){
            throw new GeneralException('Group does not exist.');
        }

        $result = DatabaseRow::createFromDbRow($result);

        return $result;
    }

    public function getGroupById(string $groupId) {
        $result = $this->groupRepository->getGroupById($groupId);

        if($result === null) {
            throw new GeneralException('Group does not exist.');
        }

        $result = DatabaseRow::createFromDbRow($result);

        return $result;
    }

    private function commonGetGroupRightForStandardOperation(string $groupId, string $operationName) {
        $rights = $this->groupRepository->getStandardGroupRightsForGroup($groupId);

        return $rights[$operationName];
    }

    public function canGroupShareDocuments(string $groupId) {
        return $this->commonGetGroupRightForStandardOperation($groupId, GroupStandardOperationRights::CAN_SHARE_DOCUMENTS);
    }

    public function canGroupExportDocuments(string $groupId) {
        return $this->commonGetGroupRightForStandardOperation($groupId, GroupStandardOperationRights::CAN_EXPORT_DOCUMENTS);
    }

    public function canGroupViewDocumentHistory(string $groupId) {
        return $this->commonGetGroupRightForStandardOperation($groupId, GroupStandardOperationRights::CAN_VIEW_DOCUMENT_HISTORY);
    }

    public function getGroupsForUser(string $userId) {
        return $this->groupRepository->getGroupsForUser($userId);
    }

    public function getUsersForGroupTitle(string $title) {
        $group = $this->getGroupByTitle($title);
        
        return $this->groupRepository->getMembersForGroup($group->groupId);
    }

    public function createNewGroup(string $title) {
        $groupId = $this->createId();

        if(!$this->groupRepository->createNewGroup($groupId, $title)) {
            throw new GeneralException('Database error.');
        }

        if(!$this->cacheFactory->invalidateCacheByNamespace(CacheNames::GROUPS) ||
            !$this->cacheFactory->invalidateCacheByNamespace(CacheNames::GROUP_MEMBERSHIPS) ||
            !$this->cacheFactory->invalidateCacheByNamespace(CacheNames::USER_GROUP_MEMBERSHIPS)) {
            throw new GeneralException('Could not invalidate cache.');
        }
    }

    public function composeQueryForGroupsWhereUserIsMember(string $userId) {
        $membershipsQb = $this->groupRepository->composeQueryForUserMemberships($userId);
        $membershipsQb->select(['groupId']);

        $qb = $this->groupRepository->composeQueryForGroups();
        $qb->andWhere('groupId IN (' . $membershipsQb->getSQL() . ')');
        
        return $qb;
    }

    public function getFirstGroupMemberForGroupTitle(string $title) {
        $group = $this->getGroupByTitle($title);

        $qb = $this->groupRepository->composeQueryForGroupMembers($group->groupId);
        $qb->limit(1)
            ->execute();

        $userId = $qb->fetch('userId');

        return $userId;
    }

    public function isUserMemberOfGroupTitle(string $userId, string $groupTitle) {
        $users = $this->getUsersForGroupTitle($groupTitle);
        return in_array($userId, $users);
    }

    /**
     * Adds user to the All users group
     * 
     * @param string $userId User ID
     */
    public function addUserToAllUsersGroup(string $userId) {
        $group = $this->getGroupByTitle(SystemGroups::ALL_USERS);

        $this->addUserToGroupId($group->groupId, $userId);
    }
}

?>