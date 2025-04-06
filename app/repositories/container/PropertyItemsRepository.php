<?php

namespace App\Repositories\Container;

use App\Repositories\ARepository;

class PropertyItemsRepository extends ARepository {
    public function composeQueryForPropertyItems(bool $onlyActive = true) {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('property_items_user_relation');

        if($onlyActive) {
            $qb->where('isActive = 1');
        }

        return $qb;
    }

    public function getFirstEntryForPropertyItem(string $itemId) {
        $qb = $this->composeQueryForPropertyItems();
        $qb->andWhere('itemId = ?', [$itemId])
            ->orderBy('dateCreated', 'ASC')
            ->limit(1)
            ->execute();

        return $qb->fetch();
    }

    public function createNewUserItemRelation(string $relationId, string $userId, string $itemId) {
        $qb = $this->qb(__METHOD__);

        $qb->insert('property_items_user_relation', ['relationId', 'userId', 'itemId'])
            ->values([$relationId, $userId, $itemId])
            ->execute();

        return $qb->fetchBool();
    }

    public function updateExistingUserItemRelationsForItemId(string $itemId, array $data) {
        $qb = $this->qb(__METHOD__);

        $qb->update('property_items_user_relation')
            ->set($data)
            ->where('itemId = ?', [$itemId])
            ->andWhere('isActive = 1')
            ->execute();

        return $qb->fetchBool();
    }

    public function getPropertyItemsForUser(string $userId) {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('property_items_user_relation')
            ->where('userId = ?', [$userId])
            ->andWhere('isActive = 1')
            ->orderBy('dateCreated', 'DESC')
            ->execute();

        return $qb->fetchAll();
    }
}

?>