<?php

namespace App\Repositories\Container;

use App\Core\DatabaseConnection;
use App\Logger\Logger;
use App\Repositories\ARepository;

class GroupRepository extends ARepository {
    public function __construct(DatabaseConnection $db, Logger $logger) {
        parent::__construct($db, $logger);
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
}

?>