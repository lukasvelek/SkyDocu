<?php

namespace App\Repositories\Container;

use App\Repositories\ARepository;

class PropertyItemsRepository extends ARepository {
    public function composeQueryForPropertyItems() {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('property_items_user_relation');

        return $qb;
    }
}

?>