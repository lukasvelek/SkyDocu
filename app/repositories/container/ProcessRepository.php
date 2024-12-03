<?php

namespace App\Repositories\Container;

use App\Repositories\ARepository;

class ProcessRepository extends ARepository {
    public function commonComposeQuery() {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('processes');

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
}

?>