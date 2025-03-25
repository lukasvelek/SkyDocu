<?php

namespace App\Repositories\Container;

use App\Repositories\ARepository;

class ExternalSystemsRepository extends ARepository {
    public function composeQueryForExternalSystems() {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('external_systems');

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
}

?>