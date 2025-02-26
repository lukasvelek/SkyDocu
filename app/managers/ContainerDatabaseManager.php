<?php

namespace App\Managers;

use App\Logger\Logger;
use App\Repositories\ContainerDatabaseRepository;

class ContainerDatabaseManager extends AManager {
    private ContainerDatabaseRepository $containerDatabaseRepository;

    public function __construct(Logger $logger, EntityManager $entityManager, ContainerDatabaseRepository $containerDatabaseRepository) {
        parent::__construct($logger, $entityManager);

        $this->containerDatabaseRepository = $containerDatabaseRepository;
    }
}

?>