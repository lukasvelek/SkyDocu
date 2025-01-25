<?php

namespace App\Repositories;

class UserSubstituteRepository extends ARepository {
    public function getUserSubstitute(string $userId) {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('user_substitutes')
            ->where('userId = ?', [$userId])
            ->execute();

        return $qb->fetch();
    }

    public function insertUserSubstitute(string $entryId, string $userId, string $substituteUserId) {
        $qb = $this->qb(__METHOD__);

        $qb->insert('user_substitutes', ['entryId', 'userId', 'substituteUserId'])
            ->values([$entryId, $userId, $substituteUserId])
            ->execute();

        return $qb->fetchBool();
    }

    public function updateUserSubstitute(string $userId, string $substituteUserId) {
        $qb = $this->qb(__METHOD__);

        $qb->update('user_substitutes')
            ->set(['substituteUserId' => $substituteUserId])
            ->where('userId = ?', [$userId])
            ->execute();

        return $qb->fetchBool();
    }

    public function removeUserSubstitute(string $userId) {
        $qb = $this->qb(__METHOD__);

        $qb->delete()
            ->from('user_substitutes')
            ->where('userId = ?', [$userId])
            ->execute();

        return $qb->fetchBool();
    }
}

?>