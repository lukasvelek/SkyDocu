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
}

?>