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
}

?>