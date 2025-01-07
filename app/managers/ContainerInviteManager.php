<?php

namespace App\Managers;

use App\Core\Datetypes\DateTime;
use App\Core\DB\DatabaseRow;
use App\Exceptions\GeneralException;
use App\Exceptions\NonExistingEntityException;
use App\Logger\Logger;
use App\Repositories\ContainerInviteRepository;

class ContainerInviteManager extends AManager {
    private ContainerInviteRepository $containerInviteRepository;

    public function __construct(Logger $logger, EntityManager $entityManager, ContainerInviteRepository $containerInviteRepository) {
        parent::__construct($logger, $entityManager);

        $this->containerInviteRepository = $containerInviteRepository;
    }

    public function createContainerInvite(string $containerId, string $dateValid) {
        $inviteId = $this->createId(EntityManager::CONTAINER_INVITES);

        if(!$this->containerInviteRepository->createContainerInvite($inviteId, $containerId, $dateValid)) {
            throw new GeneralException('Database error.', null, false);
        }
    }

    public function removeContainerInvite(string $inviteId) {
        if(!$this->containerInviteRepository->removeContainerInvite($inviteId)) {
            throw new GeneralException('Database error.', null, false);
        }
    }

    public function getInviteForContainer(string $containerId) {
        $result = $this->containerInviteRepository->getInviteForContainer($containerId);

        if($result === null) {
            throw new GeneralException('No invite for container exists.', null, false);
        }

        return DatabaseRow::createFromDbRow($result);
    }

    public function getInviteById(string $inviteId, bool $checkDate = true) {
        $result = $this->containerInviteRepository->getInviteById($inviteId, $checkDate);

        if($result === null) {
            throw new NonExistingEntityException('No invite exists.', null, false);
        }

        return DatabaseRow::createFromDbRow($result);
    }

    public function insertNewContainerInviteUsage(string $inviteId, string $containerId, array $data) {
        $entryId = $this->createId(EntityManager::CONTAINER_INVITE_USAGE);

        if(!$this->containerInviteRepository->insertContainerInviteUsage($entryId, $inviteId, $containerId, serialize($data))) {
            throw new GeneralException('Database error.', null, false);
        }
    }

    public function composeQueryForContainerInviteUsages(string $containerId) {
        return $this->containerInviteRepository->composeQueryForContainerInviteUsages($containerId);
    }

    public function disableContainerInvite(string $inviteId) {
        $date = new DateTime();
        $date->modify('-1d');

        $data = [
            'dateValid' => $date->getResult()
        ];

        if(!$this->containerInviteRepository->updateContainerInvite($inviteId, $data)) {
            throw new GeneralException('Database error.', null, false);
        }
    }

    public function updateContainerInviteUsage(string $entryId, array $data) {
        if(!$this->containerInviteRepository->updateContainerInviteUsage($entryId, $data)) {
            throw new GeneralException('Database error.', null, false);
        }
    }

    public function deleteContainerInviteUsage(string $entryId) {
        if(!$this->containerInviteRepository->deleteContainerInviteUsage($entryId)) {
            throw new GeneralException('Database error.', null, false);
        }
    }

    public function getInviteUsageById(string $entryId) {
        $result = $this->containerInviteRepository->getInviteUsageById($entryId);

        if($result === null) {
            throw new NonExistingEntityException('No entry exists.', null, false);
        }

        return DatabaseRow::createFromDbRow($result);
    }
}

?>