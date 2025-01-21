<?php

namespace App\Repositories;

class ContainerRepository extends ARepository {
    public function composeQueryForContainers() {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('containers')
            ->orderBy('dateCreated', 'DESC');

        return $qb;
    }

    public function checkTitleExists(string $title) {
        $qb = $this->qb(__METHOD__);

        $qb->select(['containerId'])
            ->from('containers')
            ->where('title = ?', [$title])
            ->execute();

        if($qb->fetch('containerId') !== null) {
            return true;
        } else {
            return false;
        }
    }

    public function createNewContainer(array $data) {
        $qb = $this->qb(__METHOD__);

        $keys = [];
        $values = [];
        foreach($data as $k => $v) {
            $keys[] = $k;
            $values[] = $v;
        }

        $qb->insert('containers', $keys)
            ->values($values)
            ->execute();

        return $qb->fetchBool();
    }

    public function getContainerById(string $containerId) {
        $qb = $this->composeQueryForContainers()
            ->where('containerId = ?', [$containerId])
            ->execute();
            
        return $qb->fetch();
    }

    public function createNewCreationStatusEntry(string $statusId, string $containerId, int $percentFinished = 0, ?string $description = null) {
        $qb = $this->qb(__METHOD__);

        $cols = ['statusId', 'containerId', 'percentFinished'];
        $values = [$statusId, $containerId, $percentFinished];

        if($description !== null) {
            $cols[] = 'description';
            $values[] = $description;
        }

        $qb->insert('container_creation_status', $cols)
            ->values($values)
            ->execute();

        return $qb->fetchBool();
    }

    public function updateCreationStatusEntry(string $containerId, array $data) {
        $qb = $this->qb(__METHOD__);

        $qb->update('container_creation_status')
            ->set($data)
            ->where('containerId = ?', [$containerId])
            ->execute();

        return $qb->fetchBool();
    }

    public function composeQueryForContainersAwaitingCreation() {
        $qb = $this->qb(__METHOD__);

        $qb->select(['statusId', 'containerId'])
            ->from('container_creation_status')
            ->where('percentFinished = 0')
            ->andWhere('description IS NULL')
            ->orderBy('dateCreated', 'ASC');

        return $qb;
    }

    public function createNewStatusHistoryEntry(string $historyId, string $containerId, string $userId, string $description, int $oldStatus, int $newStatus) {
        $qb = $this->qb(__METHOD__);

        $qb->insert('container_status_history', [
                'historyId',
                'containerId',
                'userId',
                'description',
                'oldStatus',
                'newStatus'
            ])
            ->values([
                $historyId,
                $containerId,
                $userId,
                $description,
                $oldStatus,
                $newStatus
            ])
            ->execute();

        return $qb->fetchBool();
    }

    public function updateContainer(string $containerId, array $data) {
        $qb = $this->qb(__METHOD__);

        $qb->update('containers')
            ->set($data)
            ->where('containerId = ?', [$containerId])
            ->execute();

        return $qb->fetchBool();
    }

    public function composeQueryForContainerStatusHistory(string $containerId) {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('container_status_history')
            ->where('containerId = ?', [$containerId])
            ->orderBy('dateCreated', 'DESC');
        
        return $qb;
    }

    public function deleteContainer(string $containerId) {
        $qb = $this->qb(__METHOD__);

        $qb->delete()
            ->from('containers')
            ->where('containerId = ?', [$containerId])
            ->execute();

        return $qb->fetchBool();
    }

    public function deleteContainerStatusHistory(string $containerId) {
        $qb = $this->qb(__METHOD__);

        $qb->delete()
            ->from('container_status_history')
            ->where('containerId = ?', [$containerId])
            ->execute();

        return $qb->fetchBool();
    }

    public function deleteContainerCreationStatus(string $containerId) {
        $qb = $this->qb(__METHOD__);

        $qb->delete()
            ->from('container_creation_status')
            ->where('containerId = ?', [$containerId])
            ->execute();

        return $qb->fetchBool();
    }

    public function insertNewContainerUsageStatisticsEntry(string $entryId, string $containerId, int $totalSqlQueries, float $averageTimeTaken, string $date, float $totalTimeTaken) {
        $qb = $this->qb(__METHOD__);

        $qb->insert('container_usage_statistics', ['entryId', 'containerId', 'totalSqlQueries', 'averageTimeTaken', 'date', 'totalTimeTaken'])
            ->values([$entryId, $containerId, $totalSqlQueries, $averageTimeTaken, $date, $totalTimeTaken])
            ->execute();

        return $qb->fetchBool();
    }

    public function getContainerUsageStatisticsForDate(string $containerId, string $date) {
        $qb = $this->qb(__METHOD__);

        $qb->select(['COUNT(entryId) AS cnt'])
            ->from('container_usage_statistics')
            ->where('containerId = ?', [$containerId])
            ->andWhere('date = ?', [$date])
            ->execute();

        return $qb->fetch('cnt');
    }

    public function composeQueryForContainerUsageStatistics(string $containerId) {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('container_usage_statistics')
            ->where('containerId = ?', [$containerId]);

        return $qb;
    }

    public function deleteContainerUsageStatistics(string $containerId) {
        $qb = $this->qb(__METHOD__);

        $qb->delete()
            ->from('container_usage_statistics')
            ->where('containerId = ?', [$containerId])
            ->execute();

        return $qb->fetchBool();
    }
}

?>