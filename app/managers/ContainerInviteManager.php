<?php

namespace App\Managers;

use App\Core\Datetypes\DateTime;
use App\Core\DB\DatabaseRow;
use App\Exceptions\GeneralException;
use App\Exceptions\NonExistingEntityException;
use App\Logger\Logger;
use App\Repositories\ContainerInviteRepository;

class ContainerInviteManager extends AManager {
    private ContainerInviteRepository $cir;

    public function __construct(Logger $logger, EntityManager $entityManager, ContainerInviteRepository $cir) {
        parent::__construct($logger, $entityManager);

        $this->cir = $cir;
    }

    public function createContainerInvite(string $containerId, string $dateValid) {
        $inviteId = $this->createId(EntityManager::CONTAINER_INVITES);

        if(!$this->cir->createContainerInvite($inviteId, $containerId, $dateValid)) {
            throw new GeneralException('Database error.', null, false);
        }
    }

    public function removeContainerInvite(string $inviteId) {
        if(!$this->cir->removeContainerInvite($inviteId)) {
            throw new GeneralException('Database error.', null, false);
        }
    }

    public function getInviteForContainer(string $containerId) {
        $result = $this->cir->getInviteForContainer($containerId);

        if($result === null) {
            throw new GeneralException('No invite for container exists.', null, false);
        }

        return DatabaseRow::createFromDbRow($result);
    }

    public function getInviteById(string $inviteId, bool $checkDate = true) {
        $result = $this->cir->getInviteById($inviteId, $checkDate);

        if($result === null) {
            throw new NonExistingEntityException('No invite exists.', null, false);
        }

        return DatabaseRow::createFromDbRow($result);
    }

    public function insertNewContainerInviteUsage(string $inviteId, string $containerId, array $data) {
        $entryId = $this->createId(EntityManager::CONTAINER_INVITE_USAGE);

        if(!$this->cir->insertContainerInviteUsage($entryId, $inviteId, $containerId, serialize($data))) {
            throw new GeneralException('Database error.', null, false);
        }
    }

    public function composeQueryForContainerInviteUsages(string $containerId) {
        return $this->cir->composeQueryForContainerInviteUsages($containerId);
    }

    public function disableContainerInvite(string $inviteId) {
        $date = new DateTime();
        $date->modify('-1d');

        $data = [
            'dateValid' => $date->getResult()
        ];

        if(!$this->cir->updateContainerInvite($inviteId, $data)) {
            throw new GeneralException('Database error.', null, false);
        }
    }

    public function updateContainerInviteUsage(string $entryId, array $data) {
        if(!$this->cir->updateContainerInviteUsage($entryId, $data)) {
            throw new GeneralException('Database error.', null, false);
        }
    }

    public function deleteContainerInviteUsage(string $entryId) {
        if(!$this->cir->deleteContainerInviteUsage($entryId)) {
            throw new GeneralException('Database error.', null, false);
        }
    }

    public function getInviteUsageById(string $entryId) {
        $result = $this->cir->getInviteUsageById($entryId);

        if($result === null) {
            throw new NonExistingEntityException('No entry exists.', null, false);
        }

        return DatabaseRow::createFromDbRow($result);
    }
}

?>