<?php

namespace App\Repositories;

use App\Core\DatabaseConnection;
use App\Logger\Logger;

class ContainerRepository extends ARepository {
    public function __construct(DatabaseConnection $db, Logger $logger) {
        parent::__construct($db, $logger);
    }

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

    public function createNewContainer(string $containerId, string $userId, string $title, string $description, string $databaseName) {
        $qb = $this->qb(__METHOD__);

        $qb->insert('containers', ['containerId', 'title', 'description', 'userId', 'databaseName'])
            ->values([$containerId, $title, $description, $userId, $databaseName])
            ->execute();

        return $qb->fetchBool();
    }

    public function getContainerById(string $containerId) {
        $qb = $this->composeQueryForContainers();

        $qb->where('containerId = ?', [$containerId])
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

    public function updateCreationStatusEntry(string $statusId, int $percentFinished, ?string $description = null) {
        $data = [
            'percentFinished' => $percentFinished
        ];

        if($description !== null) {
            $data['description'] = $description;
        }

        $qb = $this->qb(__METHOD__);

        $qb->update('container_creation_status')
            ->set($data)
            ->where('statusId = ?', [$statusId])
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
}

?>