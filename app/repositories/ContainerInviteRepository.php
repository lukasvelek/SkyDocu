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
            ->andWhere('dateValid >= current_timestamp()')
            ->execute();

        return $qb->fetch();
    }

    public function getInviteById(string $inviteId, bool $checkDate = true) {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('container_invites')
            ->where('inviteId = ?', [$inviteId]);

        if($checkDate) {
            $qb->andWhere('dateValid >= current_timestamp()');
        }

        $qb->execute();

        return $qb->fetch();
    }

    public function insertContainerInviteUsage(string $entryId, string $inviteId, string $containerId, string $serializedData) {
        $qb = $this->qb(__METHOD__);
        
        $qb->insert('container_invite_usage', ['entryId', 'inviteId', 'containerId', 'data'])
            ->values([$entryId, $inviteId, $containerId, $serializedData])
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

    public function updateContainerInvite(string $inviteId, array $data) {
        $qb = $this->qb(__METHOD__);

        $qb->update('container_invites')
            ->set($data)
            ->where('inviteId = ?', [$inviteId])
            ->execute();

        return $qb->fetchBool();
    }
}

?>