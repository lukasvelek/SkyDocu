<?php

namespace App\Repositories;

use App\Core\Caching\Cache;
use App\Core\Caching\CacheNames;
use App\Entities\GroupEntity;
use PeeQL\Operations\QueryOperation;
use PeeQL\Result\QueryResult;

class GroupRepository extends ARepository {
    private Cache $groupCache;
    private Cache $groupTitleToIdMappingCache;
    private Cache $userGroupMembershipsCache;

    private function getGroupCache() {
        if(!isset($this->groupCache)) {
            $this->groupCache = $this->cacheFactory->getCache(CacheNames::GROUPS);
        }

        return $this->groupCache;
    }

    private function getGroupTitleToIdMappingCache() {
        if(!isset($this->groupCache)) {
            $this->groupTitleToIdMappingCache = $this->cacheFactory->getCache(CacheNames::GROUP_TITLE_TO_ID_MAPPING);
        }

        return $this->groupTitleToIdMappingCache;
    }

    private function getUserGroupMembershipsCache() {
        if(!isset($this->groupCache)) {
            $this->userGroupMembershipsCache = $this->cacheFactory->getCache(CacheNames::USER_GROUP_MEMBERSHIPS);
        }

        return $this->userGroupMembershipsCache;
    }

    public function getGroupEntityById(string $groupId) {
        $this->getGroupCache();

        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])  
            ->from('groups')
            ->where('groupId = ?', [$groupId]);

        return $this->groupCache->load($groupId, function() use ($qb) {
            return GroupEntity::createEntityFromDbRow($qb->execute()->fetch());
        });
    }

    public function getGroupEntityByTitle(string $title) {
        $this->getGroupTitleToIdMappingCache();

        $qb = $this->qb(__METHOD__);

        $qb->select(['groupId'])
            ->from('groups')
            ->where('title = ?', [$title]);

        $groupId = $this->groupTitleToIdMappingCache->load($title, function() use ($qb) {
            return $qb->execute()->fetch('groupId');
        });

        if($groupId === null) {
            return $groupId;
        }

        return $this->getGroupEntityById($groupId);
    }

    public function createNewGroup(string $groupId, string $title, ?string $containerId = null) {
        $keys = ['groupId', 'title'];
        $values = [$groupId, $title];

        if($containerId !== null) {
            $keys[] = 'containerId';
            $values[] = $containerId;
        }

        $qb = $this->qb(__METHOD__);

        $qb->insert('groups', $keys)
            ->values($values)
            ->execute();

        return $qb->fetchBool();
    }

    public function removeGroup(string $groupId) {
        $qb = $this->qb(__METHOD__);

        $qb->delete()
            ->from('groups')
            ->where('groupId = ?', [$groupId])
            ->execute();

        return $qb->fetchBool();
    }

    public function composeQueryForGroups() {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('groups');

        return $qb;
    }

    public function getGroupById(string $groupId) {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])  
            ->from('groups')
            ->where('groupId = ?', [$groupId])
            ->execute();

        return $qb->fetch();
    }

    public function getGroupByTitle(string $title) {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('groups')
            ->where('title = ?', [$title])
            ->execute();

        return $qb->fetch();
    }

    public function getGroupsForUser(string $userId, bool $force = false) {
        $this->getUserGroupMembershipsCache();

        $qb = $this->qb(__METHOD__);

        $qb->select(['groupId'])
            ->from('group_users')
            ->where('userId = ?', [$userId]);

        return $this->userGroupMembershipsCache->load($userId, function() use ($qb) {
            $qb->execute();

            $groups = [];
            while($row = $qb->fetchAssoc()) {
                $groups[] = $row['groupId'];
            }

            return $groups;
        }, [], $force);
    }

    public function get(QueryOperation $operation): QueryResult {
        return $this->processPeeQL('groups', $operation);
    }
}

?>