<?php

namespace App\Repositories;

use App\Entities\SystemServiceEntity;

class SystemServicesRepository extends ARepository {
    public function getAllServices() {
        $qb = $this->qb(__METHOD__);
        
        $qb ->select(['*'])
            ->from('system_services')
            ->orderBy('dateStarted', 'DESC')
            ->execute();

        $entities = [];
        while($row = $qb->fetchAssoc()) {
            $entities[] = SystemServiceEntity::createEntityFromDbRow($row);
        }

        return $entities;
    }

    public function getServiceById(string $id) {
        $qb = $this->qb(__METHOD__);
        
        $qb ->select(['*'])
            ->from('system_services')
            ->where('serviceId = ?', [$id])
            ->execute();

        return SystemServiceEntity::createEntityFromDbRow($qb->fetch());
    }

    public function getServiceByTitle(string $title) {
        $qb = $this->qb(__METHOD__);
        
        $qb ->select(['*'])
            ->from('system_services')
            ->where('title = ?', [$title])
            ->execute();

        return SystemServiceEntity::createEntityFromDbRow($qb->fetch());
    }

    public function updateService(string $serviceId, array $data) {
        $qb = $this->qb(__METHOD__);

        $qb ->update('system_services')
            ->set($data)
            ->where('serviceId = ?', [$serviceId])
            ->execute();

        return $qb->fetchBool();
    }

    public function composeQueryForServices() {
        $qb = $this->qb(__METHOD__);
        
        $qb ->select(['*'])
            ->from('system_services')
            ->orderBy('dateStarted', 'DESC');

        return $qb;
    }

    public function createHistoryEntry(string $historyId, string $serviceId, int $status, string $args, ?string $exceptionText = null) {
        $qb = $this->qb(__METHOD__);

        $keys = ['historyId', 'serviceId', 'status', 'args'];
        $values = [$historyId, $serviceId, $status, $args];

        if($exceptionText !== null) {
            $keys[] = 'exception';
            $values[] = $exceptionText;
        }

        $qb->insert('system_services_history', $keys)
            ->values($values)
            ->execute();

        return $qb->fetchAll();
    }

    public function composeQueryForServiceHistory(string $serviceId) {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('system_services_history')
            ->where('serviceId = ?', [$serviceId]);

        return $qb;
    }

    public function getChildrenCountForServiceId(string $serviceId) {
        $qb = $this->composeQueryForServices();

        $qb->andWhere('parentServiceId = ?', [$serviceId])
            ->select(['COUNT(*) AS cnt'])
            ->execute();

        return $qb->fetch('cnt');
    }
}

?>