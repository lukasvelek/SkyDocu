<?php

namespace App\Managers;

use App\Exceptions\GeneralException;
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

        return $result;
    }

    public function getContainerForInvite(string $inviteId) {
        $result = $this->cir->getContainerByInvite($inviteId);

        if($result === null) {
            throw new GeneralException('No container for invite exists.', null, false);
        }

        return $result;
    }

    public function insertNewContainerInviteUsage(string $inviteId, string $containerId, string $userId) {
        if(!$this->cir->insertContainerInviteUsage($inviteId, $containerId, $userId)) {
            throw new GeneralException('Database error.', null, false);
        }
    }

    public function composeQueryForContainerInviteUsages(string $containerId) {
        return $this->cir->composeQueryForContainerInviteUsages($containerId);
    }
}

?>