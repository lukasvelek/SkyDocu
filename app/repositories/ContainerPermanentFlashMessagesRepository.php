<?php

namespace app\Repositories;

use App\Repositories\ARepository;
use QueryBuilder\QueryBuilder;

/**
 * ContainerPermanentFlashMessagesRepository contains low-level API methods for working with container flash messages
 * 
 * @author Lukas Velek
 */
class ContainerPermanentFlashMessagesRepository extends ARepository {
    /**
     * Composes a QueryBuilder instance for container_permanent_flash_messages database table
     */
    public function composeQueryForPermanentFlashMessages(): QueryBuilder {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('container_permanent_flash_messages');

        return $qb;
    }

    /**
     * Creates a new permanent flash message
     * 
     * @param array $data Data array
     */
    public function createNewPermanentFlashMessage(array $data): bool {
        $qb = $this->qb(__METHOD__);

        $qb->insert('container_permanent_flash_messages', array_keys($data))
            ->values(array_values($data))
            ->execute();

        return $qb->fetchBool();
    }

    /**
     * Updates existing permanent flash message
     * 
     * @param string $messageId Message ID
     * @param array $data Data array
     */
    public function updatePermanentFlashMessage(string $messageId, array $data): bool {
        $qb = $this->qb(__METHOD__);

        $qb->update('container_permanent_flash_messages')
            ->set($data)
            ->where('messageId = ?', [$messageId])
            ->execute();

        return $qb->fetchBool();
    }

    /**
     * Returns the active permanent flash message
     */
    public function getActivePermanentFlashMessage(): ?array {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('container_permanent_flash_messages')
            ->where('isActive = 1')
            ->execute();

        return $qb->fetch();
    }
}