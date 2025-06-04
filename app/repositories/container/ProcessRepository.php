<?php

namespace App\Repositories\Container;

use App\Constants\Container\ProcessStatus;
use App\Repositories\ARepository;
use QueryBuilder\QueryBuilder;

class ProcessRepository extends ARepository {
    public function commonComposeQuery(): QueryBuilder {
        $qb = $this->qb(__METHOD__);
        
        $qb->select(['*'])
            ->from('processes');

        return $qb;
    }

    public function addNewProcess(string $processId, string $uniqueProcessId, string $title, string $description, string $definition, string $userId, int $status) {
        $qb = $this->qb(__METHOD__);

        $qb->insert('processes', ['processId', 'uniqueProcessId', 'title', 'description', 'definition', 'userId', 'status'])
            ->values([$processId, $uniqueProcessId, $title, $description, $definition, $userId, $status])
            ->execute();

        return $qb->fetchBool();
    }

    public function updateProcess(string $processId, array $data) {
        $qb = $this->qb(__METHOD__);

        $qb->update('processes')
            ->set($data)
            ->where('processId = ?', [$processId])
            ->execute();

        return $qb->fetchBool();
    }

    public function getDistributionProcessForUniqueProcessId(string $uniqueProcessId) {
        $qb = $this->commonComposeQuery();

        $qb->andWhere('uniqueProcessId = ?', [$uniqueProcessId])
            ->andWhere('status = ?', [ProcessStatus::IN_DISTRIBUTION])
            ->execute();

        return $qb->fetch();
    }

    public function removeCurrentDistributionProcessFromDistributionForUniqueProcessId(string $uniqueProcessId) {
        $qb = $this->qb(__METHOD__);

        $qb->update('processes')
            ->set(['status' => ProcessStatus::NOT_IN_DISTRIBUTION])
            ->where('uniqueProcessId = ?', [$uniqueProcessId])
            ->andWhere('status = ?', [ProcessStatus::IN_DISTRIBUTION])
            ->execute();

        return $qb->fetchBool();
    }

    public function addCurrentDistributionprocessToDistributionForUniqueProcessId(string $processId, string $uniqueProcessId) {
        $qb = $this->qb(__METHOD__);

        $qb->update('processes')
            ->set(['status' => ProcessStatus::IN_DISTRIBUTION])
            ->where('uniqueProcessId = ?', [$uniqueProcessId])
            ->andWhere('processId = ?', [$processId])
            ->andWhere('status = ?', [ProcessStatus::NOT_IN_DISTRIBUTION])
            ->execute();

        return $qb->fetchBool();
    }

    public function composeQueryForAvailableProcesses() {
        $qb = $this->commonComposeQuery();

        $qb->andWhere('status = 1');

        return $qb;
    }

    /**
     * Returns process by its ID
     * 
     * @param string $processId Process ID
     */
    public function getProcessById(string $processId) {
        $qb = $this->commonComposeQuery();

        $qb->andWhere('processId = ?', [$processId])
            ->execute();

        return $qb->fetch();
    }
}

?>