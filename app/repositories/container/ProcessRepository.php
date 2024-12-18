<?php

namespace App\Repositories\Container;

use App\Constants\Container\ProcessStatus;
use App\Repositories\ARepository;

class ProcessRepository extends ARepository {
    public function commonComposeQuery(bool $onlyNotFinished = true) {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('processes');

        if($onlyNotFinished) {
            $qb->andWhere('status = ?', [ProcessStatus::IN_PROGRESS]);
        }

        return $qb;
    }

    public function insertNewProcess(string $processId, array $data) {
        $keys = ['processId'];
        $values = [$processId];
        foreach($data as $key => $value) {
            $keys[] = $key;
            $values[] = $value;
        }

        $qb = $this->qb(__METHOD__);

        $qb->insert('processes', $keys)
            ->values($values)
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

    public function getProcessTypes() {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('process_types')
            ->execute();

        $types = [];
        while($row = $qb->fetchAssoc()) {
            $types[] = $row;
        }

        return $types;
    }

    public function getProcessTypeByKey(string $key) {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('process_types')
            ->where('typeKey = ?', [$key])
            ->execute();

        return $qb->fetch();
    }

    public function getProcessesForDocument(string $documentId, bool $activeOnly = true) {
        $qb = $this->qb(__METHOD__);
        
        $qb->select(['*'])
            ->from('processes')
            ->where('documentId = ?', [$documentId]);

        if($activeOnly) {
            $qb->andWhere('status <> ?', [ProcessStatus::FINISHED]);
        }

        $qb->execute();

        $processes = [];
        while($row = $qb->fetchAssoc()) {
            $processes[] = $row;
        }

        return $processes;
    }

    public function getProcessById(string $processId) {
        $qb = $this->commonComposeQuery(false);

        $qb->andWhere('processId = ?', [$processId])
            ->execute();

        return $qb->fetch();
    }

    public function getActiveProcessCountForDocuments(array $documentIds) {
        $qb = $this->qb(__METHOD__);

        $qb->select(['documentId', 'COUNT(processId) AS cnt'])
            ->from('processes')
            ->where('( ' . $qb->getColumnInValues('documentId', $documentIds) . ' )')
            ->andWhere('( status <> ? )', [ProcessStatus::FINISHED]);

        $qb->execute();

        $result = [];
        while($row = $qb->fetchAssoc()) {
            $result[$row['documentId']] = $row['cnt'];
        }

        return $result;
    }
}

?>