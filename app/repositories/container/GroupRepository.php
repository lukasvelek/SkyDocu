<?php

namespace App\Repositories\Container;

use App\Constants\Container\GroupStandardOperationRights;
use App\Core\Caching\Cache;
use App\Core\Caching\CacheNames;
use App\Core\DatabaseConnection;
use App\Logger\Logger;
use App\Repositories\ARepository;

class GroupRepository extends ARepository {
    private Cache $groupStandardOperationRightsCache;

    public function __construct(DatabaseConnection $db, Logger $logger) {
        parent::__construct($db, $logger);

        $this->groupStandardOperationRightsCache = $this->cacheFactory->getCache(CacheNames::GROUP_STANDARD_OPERATIONS_RIGHTS);
    }

    public function getGroupsForUser(string $userId) {
        $qb = $this->qb(__METHOD__);

        $qb->select(['groupId'])
            ->from('group_users_relation')
            ->where('userId = ?', [$userId])
            ->execute();

        $groups = [];
        while($row = $qb->fetchAssoc()) {
            $groups[] = $row['groupId'];
        }

        return $groups;
    }

    public function getGroupById(string $groupId) {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('groups')
            ->where('groupId = ?', [$groupId])
            ->execute();

        return $qb->fetch();
    }

    public function getGroupByTitle(string $groupTitle) {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('groups')
            ->where('title = ?', [$groupTitle])
            ->execute();

        return $qb->fetch();
    }

    public function composeQueryForGroups() {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('groups');

        return $qb;
    }

    public function composeQueryForGroupMembers(string $groupId) {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('group_users_relation')
            ->where('groupId = ?', [$groupId]);

        return $qb;
    }

    public function getMembersForGroup(string $groupId) {
        $qb = $this->composeQueryForGroupMembers($groupId);
        $qb->execute();

        $members = [];
        while($row = $qb->fetchAssoc()) {
            $members[] = $row['userId'];
        }

        return $members;
    }

    public function addUserToGroup(string $relationId, string $groupId, string $userId) {
        $qb = $this->qb(__METHOD__);

        $qb->insert('group_users_relation', ['relationId', 'groupId', 'userId'])
            ->values([$relationId, $groupId, $userId])
            ->execute();

        return $qb->fetchBool();
    }

    public function removeUserFromGroup(string $groupId, string $userId) {
        $qb = $this->qb(__METHOD__);

        $qb->delete()
            ->from('group_users_relation')
            ->where('groupId = ?', [$groupId])
            ->andWhere('userId = ?', [$userId])
            ->execute();

        return $qb->fetchBool();
    }

    public function getStandardGroupRightsForGroup(string $groupId) {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('group_rights_standard_operations')
            ->where('groupId = ?', [$groupId]);

        return $this->groupStandardOperationRightsCache->load($groupId, function() use ($qb) {
            $result = $qb->execute()->fetchAll();

            if($result === false || $result === null) {
                return [
                    GroupStandardOperationRights::CAN_SHARE_DOCUMENTS => false,
                    GroupStandardOperationRights::CAN_EXPORT_DOCUMENTS => false,
                    GroupStandardOperationRights::CAN_VIEW_DOCUMENT_HISTORY => false
                ];
            }

            $share = false;
            $export = false;
            $viewDocumentHistory = false;
            foreach($result as $result) {
                if($result[GroupStandardOperationRights::CAN_SHARE_DOCUMENTS] == '1') {
                    $share = true;
                }
                if($result[GroupStandardOperationRights::CAN_EXPORT_DOCUMENTS] == '1') {
                    $export = true;
                }
                if($result[GroupStandardOperationRights::CAN_VIEW_DOCUMENT_HISTORY] == '1') {
                    $viewDocumentHistory = true;
                }
            }

            return [
                GroupStandardOperationRights::CAN_SHARE_DOCUMENTS => $share,
                GroupStandardOperationRights::CAN_EXPORT_DOCUMENTS => $export,
                GroupStandardOperationRights::CAN_VIEW_DOCUMENT_HISTORY => $viewDocumentHistory
            ];
        });
    }

    public function createNewGroup(string $groupId, string $groupTitle) {
        $qb = $this->qb(__METHOD__);

        $qb->insert('groups', ['groupId', 'title'])
            ->values([$groupId, $groupTitle])
            ->execute();

        return $qb->fetchBool();
    }
}

?>