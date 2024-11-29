<?php

namespace App\Repositories;

class ContentRepository extends ARepository {
    public function checkIdIsUnique(string $tableName, string $primaryKeyName, string $id) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from($tableName)
            ->where($primaryKeyName . ' = ?', [$id])
            ->execute();

        if($qb->fetch($primaryKeyName)) {
            return false;
        } else {
            return true;
        }
    }
}

?>