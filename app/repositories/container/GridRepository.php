<?php

namespace App\Repositories\Container;

use App\Repositories\ARepository;

class GridRepository extends ARepository {
    public function getGridConfigurationForGridName(string $gridName) {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('grid_configuration')
            ->where('gridName = ?', [$gridName])
            ->execute();

        return $qb->fetch();
    }

    public function deleteGridConfiguration(string $gridName) {
        return $this->deleteEntryById('grid_configuration', 'gridName', $gridName);
    }

    public function insertGridConfiguration(string $configurationId, string $gridName, string $columns) {
        $qb = $this->qb(__METHOD__);

        $qb->insert('grid_configuration', ['configurationId', 'gridName', 'columnConfiguration'])
            ->values([$configurationId, $gridName, $columns])
            ->execute();

        return $qb->fetchBool();
    }

    public function updateGridConfiguration(string $gridName, string $columns) {
        $qb = $this->qb(__METHOD__);

        $qb->update('grid_configuration')
            ->set(['columnConfiguration' => $columns])
            ->where('gridName = ?', [$gridName])
            ->execute();

        return $qb->fetchBool();
    }

    public function composeQueryForGridConfigurations() {
        $qb = $this->qb(__METHOD__);
        
        $qb->select(['*'])
            ->from('grid_configuration');

        return $qb;
    }
}

?>