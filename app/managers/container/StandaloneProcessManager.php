<?php

namespace App\Managers\Container;

use App\Constants\Container\StandaloneProcesses;
use App\Core\DB\DatabaseRow;
use App\Entities\UserEntity;
use App\Exceptions\GeneralException;
use App\Logger\Logger;
use App\Managers\AManager;
use App\Managers\EntityManager;
use App\Managers\UserManager;

class StandaloneProcessManager extends AManager {
    public ProcessManager $processManager;
    private UserEntity $currentUser;
    private UserManager $userManager;

    public function __construct(
        Logger $logger,
        ?EntityManager $entityManager,
        ProcessManager $processManager,
        UserEntity $currentUser,
        UserManager $userManager
    ) {
        parent::__construct($logger, $entityManager);

        $this->processManager = $processManager;
        $this->currentUser = $currentUser;
        $this->userManager = $userManager;
    }

    public function startHomeOffice(array $data) {
        $admin = $this->userManager->getUserByUsername('admin');

        $currentOfficerId = $admin->getId();
        $workflow = [
            $admin->getId()
        ];

        $processId = $this->processManager->startProcess(null, StandaloneProcesses::HOME_OFFICE, $this->currentUser->getId(), $currentOfficerId, $workflow);

        $this->saveProcessData($processId, $data);
    }

    private function saveProcessData(string $processId, array $data) {
        $entryId = $this->createId(EntityManager::C_PROCESS_DATA);

        if(array_key_exists('btn_submit', $data)) {
            unset($data['btn_submit']);
        }

        if(!$this->processManager->processRepository->insertNewProcessData($entryId, $processId, serialize($data))) {
            throw new GeneralException('Database error.');
        }
    }

    public function getProcessData(string $processId) {
        return $this->processManager->processRepository->getProcessDataForProcess($processId);
    }

    public function updateProcessType(string $typeKey, array $data) {
        if(!$this->processManager->processRepository->updateProcessType($typeKey, $data)) {
            throw new GeneralException('Database error.');
        }
    }

    public function getEnabledProcessTypes() {
        $qb = $this->processManager->processRepository->composeQueryForProcessTypes();
        $qb->andWhere('isEnabled = 1')
            ->execute();

        $rows = [];
        while($row = $qb->fetchAssoc()) {
            $rows[] = DatabaseRow::createFromDbRow($row);
        }

        return $rows;
    }

    public function composeQueryForProcessTypeInstances(string $processType) {
        $qb = $this->processManager->processRepository->commonComposeQuery(false);
        $qb->andWhere('type = ?', [$processType]);
        return $qb;
    }
}

?>