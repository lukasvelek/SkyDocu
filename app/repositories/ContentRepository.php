<?php

namespace App\Repositories;

/**
 * ContentRepository is used for working with content in database tables
 * 
 * @author Lukas Velek
 */
class ContentRepository extends ARepository {
    /**
     * Checks if given $id (generated primary key value) is unique in given table or not
     * 
     * @param string $tableName Table name
     * @param string $primaryKeyName Primary key name
     * @param string $id Generated priamry key value
     */
    public function checkIdIsUnique(string $tableName, string $primaryKeyName, string $id): bool {
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

    /**
     * Checks if given $value is unique in given $table or not
     * 
     * @param string $tableName Table name
     * @param string $columnName Column name
     * @param string $value Generated value
     */
    public function checkValueIsUnique(string $tableName, string $columnName, string $value): bool {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from($tableName)
            ->where($columnName . ' = ?', [$value])
            ->execute();

        if($qb->fetch($columnName)) {
            return false;
        } else {
            return true;
        }
    }
}

?>