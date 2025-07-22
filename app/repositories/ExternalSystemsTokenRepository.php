<?php

namespace App\Repositories;

use QueryBuilder\QueryBuilder;

/**
 * ExternalSystemsTokenRepository contains low-level API methods
 * 
 * @author Lukas Velek
 */
class ExternalSystemsTokenRepository extends ARepository {
    /**
     * Inserts a new token
     * 
     * @param array $data Data array
     */
    public function insertNewToken(array $data): bool {
        $qb = $this->qb(__METHOD__);

        $qb->insert('external_system_tokens', array_keys($data))
            ->values(array_values($data))
            ->execute();

        return $qb->fetchBool();
    }

    /**
     * Returns system token row for given token
     * 
     * @param string $token Token
     */
    public function getSystemByToken(string $token): mixed {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('external_system_tokens')
            ->andWhere('token = ?', [$token])
            ->andWhere('dateValidUntil > current_timestamp()')
            ->orderBy('dateValidUntil', 'DESC')
            ->limit(1)
            ->execute();

        return $qb->fetch();
    }

    /**
     * Returns available token row for given system
     * 
     * @param string $systemId System ID
     */
    public function getAvailableTokenForExternalSystem(string $systemId): mixed {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('external_system_tokens')
            ->andWhere('systemId = ?', [$systemId])
            ->andWhere('dateValidUntil > current_timestamp()')
            ->orderBy('dateValidUntil', 'DESC')
            ->limit(1)
            ->execute();

        return $qb->fetch();
    }

    /**
     * Deletes tokens for given system
     * 
     * @param string $systemId System ID
     */
    public function deleteTokensForSystem(string $systemId): bool {
        $qb = $this->qb(__METHOD__);

        $qb->delete()
            ->from('external_system_tokens')
            ->where('systemId = ?', [$systemId])
            ->execute();

        return $qb->fetchBool();
    }

    /**
     * Deletes tokens for given container
     * 
     * @param string $containerId Container ID
     */
    public function deleteTokensForContainer(string $containerId): bool {
        $qb = $this->qb(__METHOD__);

        $qb->delete()
            ->from('external_system_tokens')
            ->where('containerId = ?', [$containerId])
            ->execute();

        return $qb->fetchBool();
    }

    /**
     * Composes a QueryBuilder instance for tokens
     */
    public function composeQueryForTokens(): QueryBuilder {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('external_system_tokens');

        return $qb;
    }
}