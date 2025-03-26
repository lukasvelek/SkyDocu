<?php

namespace App\Repositories\Container;

use App\Repositories\ARepository;

class ExternalSystemTokenRepository extends ARepository {
    public function composeQueryForExternalSystemTokens() {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('external_system_tokens');

        return $qb;
    }

    public function getAvailableTokenForExternalSystem(string $systemId) {
        $qb = $this->composeQueryForExternalSystemTokens();

        $qb->andWhere('systemId = ?', [$systemId])
            ->andWhere('dateValidUntil > current_timestamp()')
            ->orderBy('dateValidUntil', 'DESC')
            ->limit(1)
            ->execute();

        return $qb->fetch();
    }

    public function getSystemByToken(string $token) {
        $qb = $this->composeQueryForExternalSystemTokens();

        $qb->andWhere('token = ?', [$token])
            ->andWhere('dateValidUntil > current_timestamp()')
            ->orderBy('dateValidUntil', 'DESC')
            ->limit(1)
            ->execute();

        return $qb->fetch();
    }

    public function insertNewExternalSystemToken(string $tokenId, string $systemId, string $token, string $dateValidUntil) {
        $qb = $this->qb(__METHOD__);

        $qb->insert('external_system_tokens', ['tokenId', 'systemId', 'token', 'dateValidUntil'])
            ->values([$tokenId, $systemId, $token, $dateValidUntil])
            ->execute();

        return $qb->fetchBool();
    }
}

?>