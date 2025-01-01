<?php

namespace App\Repositories;

class ContainerInviteRepository extends ARepository {
    public function createContainerInvite(string $inviteId, string $containerId, string $dateValid) {
        $qb = $this->qb(__METHOD__);

        $qb->insert('container_invites', ['inviteId', 'containerId', 'dateValid'])
            ->values([$inviteId, $containerId, $dateValid])
            ->execute();

        return $qb->fetchBool();
    }

    public function removeContainerInvite(string $inviteId) {
        $qb = $this->qb(__METHOD__);

        $qb->delete()
            ->from('container_invites')
            ->where('inviteId = ?', [$inviteId])
            ->execute();

        return $qb->fetchBool();
    }

    public function getInviteForContainer(string $containerId) {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('container_invites')
            ->where('containerId = ?', [$containerId])
            ->execute();

        return $qb->fetch('inviteId');
    }

    public function getContainerByInvite(string $inviteId) {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('container_invites')
            ->where('inviteId = ?', [$inviteId])
            ->execute();

        return $qb->fetch('containerId');
    }

    public function insertContainerInviteUsage(string $inviteId, string $containerId, string $userId) {
        $qb = $this->qb(__METHOD__);
        
        $qb->insert('container_invites', ['inviteId', 'containerId', 'userId'])
            ->values([$inviteId, $containerId, $userId])
            ->execute();

        return $qb->fetchBool();
    }

    public function composeQueryForContainerInviteUsages(string $containerId) {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('container_invite_usage')
            ->where('containerId = ?', [$containerId]);

        return $qb;
    }
}

?>