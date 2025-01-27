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
            ->andWhere('active = 1')
            ->execute()
            ->limit(1)
            ->orderBy('dateCreated', 'DESC');

        return $qb->fetch();
    }

    public function insertUserAbsence(string $absenceId, string $userId, string $dateFrom, string $dateTo) {
        $qb = $this->qb(__METHOD__);

        $qb->insert('user_absence', ['absenceId', 'userId', 'dateFrom', 'dateTo'])
            ->values([$absenceId, $userId, $dateFrom, $dateTo])
            ->execute();

        return $qb->fetchBool();
    }

    public function updateUserAbsence(string $absenceId, array $data) {
        $qb = $this->qb(__METHOD__);

        $qb->update('user_absence')
            ->set($data)
            ->where('absenceId = ?', [$absenceId])
            ->execute();

        return $qb->fetchBool();
    }

    public function composeQueryForCurrentlyAbsentUsers() {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('user_absence')
            ->Where('dateFrom < current_timestamp()')
            ->andWhere('dateTo > current_timestamp()')
            ->andWhere('active = 1');

        return $qb;
    }
}

?>