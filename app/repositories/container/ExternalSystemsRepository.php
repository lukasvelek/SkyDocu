<?php

namespace App\Repositories\Container;

use App\Repositories\ARepository;

class ExternalSystemsRepository extends ARepository {
    public function composeQueryForExternalSystems() {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('external_systems')
            /*->where('isSystem = 0')*/;

        return $qb;
    }

    public function insertNewExternalSystem(string $systemId, string $title, string $description, string $login, string $password) {
        $qb = $this->qb(__METHOD__);

        $qb->insert('external_systems', ['systemId', 'title', 'description', 'login', 'password'])
            ->values([$systemId, $title, $description, $login, $password])
            ->execute();

        return $qb->fetchBool();
    }

    public function updateExternalSystem(string $systemId, array $data) {
        $qb = $this->qb(__METHOD__);

        $qb->update('external_systems')
            ->set($data)
            ->where('systemId = ?', [$systemId])
            ->execute();

        return $qb->fetchBool();
    }

    public function getExternalSystemById(string $systemId) {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('external_systems')
            ->where('systemId = ?', [$systemId])
            ->execute();

        return $qb->fetch();
    }

    public function getExternalSystemByLogin(string $login) {
        $qb = $this->qb(__METHOD__);
        
        $qb->select(['*'])
            ->from('external_systems')
            ->where('login = ?', [$login])
            ->execute();

        return $qb->fetch();
    }

    public function deleteExternalSystem(string $systemId) {
        $qb = $this->qb(__METHOD__);

        $qb->delete()
            ->from('external_systems')
            ->where('systemId = ?', [$systemId])
            ->execute();

        return $qb->fetchBool();
    }

    public function getSystemExternalSystem() {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('external_systems')
            ->where('isSystem = 1')
            ->execute();

        return $qb->fetch();
    }
}

?>