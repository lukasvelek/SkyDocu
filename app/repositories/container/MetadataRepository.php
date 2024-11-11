<?php

namespace App\Repositories\Container;

use App\Core\DatabaseConnection;
use App\Logger\Logger;
use App\Repositories\ARepository;

class MetadataRepository extends ARepository {
    public function __construct(DatabaseConnection $db, Logger $logger) {
        parent::__construct($db, $logger);
    }

    public function composeQueryForMetadata() {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('custom_metadata');

        return $qb;
    }

    public function createNewMetadata(array $data) {
        $qb = $this->qb(__METHOD__);

        $keys = [];
        $values = [];
        foreach($data as $k => $v) {
            $keys[] = $k;
            $values[] = $v;
        }

        $qb->insert('custom_metadata', $keys)
            ->values($values)
            ->execute();

        return $qb->fetchBool();
    }
}

?>