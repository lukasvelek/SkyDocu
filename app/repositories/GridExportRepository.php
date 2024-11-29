<?php

namespace App\Repositories;

use App\Managers\EntityManager;

class GridExportRepository extends ARepository {
    public function composeQueryForExports() {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('grid_exports')
            ->orderBy('dateCreated', 'DESC');

        return $qb;
    }

    public function createNewExport(string $userId, string $hash, string $gridName) {
        $exportId = $this->createEntityId(EntityManager::GRID_EXPORTS);

        $qb = $this->qb(__METHOD__);

        $qb ->insert('grid_exports', ['exportId', 'userId', 'hash', 'gridName'])
            ->values([$exportId, $userId, $hash, $gridName])
            ->execute();
        
        return $qb->fetchBool();
    }

    public function updateExportByHash(string $hash, array $data) {
        $qb = $this->qb(__METHOD__);

        $qb ->update('grid_exports')
            ->set($data)
            ->where('hash = ?', [$hash])
            ->execute();

        return $qb->fetchBool();
    }

    public function getWaitingUnlimitedExports() {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['hash'])
            ->from('grid_exports')
            ->where('entryCount IS NULL')
            ->andWhere('filename IS NULL')
            ->andWhere('dateFinished IS NULL')
            ->execute();

        $hashes = [];
        while($row = $qb->fetchAssoc()) {
            $hashes[] = $row['hash'];
        }

        return $hashes;
    }
}

?>