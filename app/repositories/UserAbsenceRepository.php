<?php

namespace App\Repositories;

class UserAbsenceRepository extends ARepository {
    public function getCurrentAbsenceForUser(string $userId) {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('user_absence')
            ->where('userId = ?', [$userId])
            ->andWhere('dateFrom < current_timestamp()')
            ->andWhere('dateTo > current_timestamp()')
            ->execute()
            ->limit(1)
            ->orderBy('dateCreated', 'DESC');

        return $qb->fetch();
    }
}

?>