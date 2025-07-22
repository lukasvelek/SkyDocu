<?php

namespace App\Core\DB;

use App\Core\HashManager;
use App\Managers\EntityManager;

/**
 * Common class for all database migrations for containers
 * 
 * @author Lukas Velek
 */
abstract class AContainerBaseMigration extends ABaseMigration {
    protected string $containerId;

    /**
     * Sets container ID
     * 
     * @param string $containerId Container ID
     */
    public function setContainerId(string $containerId) {
        $this->containerId = $containerId;
    }

    protected function getId(string $tableName, ?string $primaryKeyName = null): ?string {
        if($primaryKeyName === null) {
            $primaryKeyName = EntityManager::getPrimaryKeyNameByCategory($tableName, true);
        }

        $runs = 0;
        $maxRuns = 1000;

        $final = null;
        while($runs < $maxRuns) {
            $id = HashManager::createEntityId();

            $result = $this->conn->query('SELECT COUNT(' . $primaryKeyName . ') AS cnt FROM ' . $tableName . ' WHERE ' . $primaryKeyName . ' = \'' . $id . '\'');

            if($result !== false) {
                foreach($result as $row) {
                    if($row['cnt'] == 0) {
                        $final = $id;
                        break;
                    }
                }
            }

            $runs++;
        }

        return $final;
    }
}

?>