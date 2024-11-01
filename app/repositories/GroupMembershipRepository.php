<?php

namespace App\Repositories;

use App\Core\Caching\Cache;
use App\Core\Caching\CacheNames;
use App\Core\DatabaseConnection;
use App\Core\DB\DatabaseRow;
use App\Logger\Logger;

class GroupMembershipRepository extends ARepository {
    private Cache $groupMembershipCache;

    public function __construct(DatabaseConnection $db, Logger $logger) {
        parent::__construct($db, $logger);

        $this->groupMembershipCache = $this->cacheFactory->getCache(CacheNames::GROUP_MEMBERSHIPS);
    }

    public function getMemberUserIdsForGroupId(string $groupId) {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('group_users')
            ->where('groupId = ?', [$groupId]);

        return $this->groupMembershipCache->load($groupId, function() use ($qb) {
            $qb->execute();

            $userIds = [];
            while($row = $qb->fetchAssoc()) {
                $userIds[] = $row['userId'];
            }

            return $userIds;
        });
    }

    public function addUserToGroup(string $groupUserId, string $groupId, string $userId) {
        $qb = $this->qb(__METHOD__);

        $qb->insert('group_users', ['groupUserId', 'groupId', 'userId'])
            ->values([$groupUserId, $groupId, $userId])
            ->execute();

        return $qb->fetchBool();
    }

    public function removeUserFromGroup(string $groupId, string $userId) {
        $qb = $this->qb(__METHOD__);

        $qb->delete()
            ->from('group_users')
            ->where('groupId = ?', [$groupId])
            ->andWhere('userId = ?', [$userId])
            ->execute();

        return $qb->fetchBool();
    }

    public function composeQueryForGroupUsers(string $groupId) {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('group_users')
            ->where('groupId = ?', [$groupId]);

        return $qb;
    }

    public function getGroupUsersForGroupId(string $groupId) {
        $qb = $this->composeQueryForGroupUsers($groupId);

        $qb->execute();

        $users = [];
        while($row = $qb->fetchAssoc()) {
            $users[] = $row['userId'];
        }

        return $users;
    }
}

?>