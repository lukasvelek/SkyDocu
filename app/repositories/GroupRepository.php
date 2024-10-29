<?php

namespace App\Repositories;

use App\Core\Caching\Cache;
use App\Core\Caching\CacheNames;
use App\Core\DatabaseConnection;
use App\Entities\GroupEntity;
use App\Logger\Logger;

class GroupRepository extends ARepository {
    private Cache $groupCache;
    private Cache $groupTitleToIdMappingCache;

    public function __construct(DatabaseConnection $db, Logger $logger) {
        parent::__construct($db, $logger);

        $this->groupCache = $this->cacheFactory->getCache(CacheNames::GROUPS);
        $this->groupTitleToIdMappingCache = $this->cacheFactory->getCache(CacheNames::GROUP_TITLE_TO_ID_MAPPING);
    }

    public function getGroupEntityById(string $groupId) {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])  
            ->from('groups')
            ->where('groupId = ?', [$groupId]);

        return $this->groupCache->load($groupId, function() use ($qb) {
            return GroupEntity::createEntityFromDbRow($qb->execute()->fetch());
        });
    }

    public function getGroupByTitle(string $title) {
        $qb = $this->qb(__METHOD__);

        $qb->select(['groupId'])
            ->from('groups')
            ->where('title = ?', [$title]);

        $groupId = $this->groupTitleToIdMappingCache->load($title, function() use ($qb) {
            return $qb->execute()->fetch('groupId');
        });

        if($groupId === null) {
            return $groupId;
        }

        return $this->getGroupEntityById($groupId);
    }

    public function createNewGroup(string $groupId, string $title) {
        $qb = $this->qb(__METHOD__);

        $qb->insert('groups', ['groupId', 'title'])
            ->values([$groupId, $title])
            ->execute();

        return $qb->fetchBool();
    }
}

?>