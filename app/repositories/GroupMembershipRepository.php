<?php

namespace App\Repositories;

use App\Core\Caching\CacheNames;

class GroupMembershipRepository extends ARepository {
    public function getMemberUserIdsForGroupId(string $groupId) {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('group_users')
            ->where('groupId = ?', [$groupId]);

        $cache = $this->cacheFactory->getCache(CacheNames::GROUP_MEMBERSHIPS);

        return $cache->load($groupId, function() use ($qb) {
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