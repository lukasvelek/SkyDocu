<?php

namespace App\Managers\Container;

use App\Core\Caching\CacheNames;
use App\Core\DB\DatabaseRow;
use App\Logger\Logger;
use App\Managers\AManager;
use App\Managers\EntityManager;
use App\Repositories\Container\FolderRepository;
use App\Repositories\Container\GroupRepository;

class FolderManager extends AManager {
    private FolderRepository $fr;
    private GroupRepository $gr;

    public function __construct(Logger $logger, EntityManager $entityManager, FolderRepository $fr, GroupRepository $gr) {
        parent::__construct($logger, $entityManager);

        $this->fr = $fr;
        $this->gr = $gr;
    }

    public function getVisibleFoldersForUser(string $userId) {
        $cache = $this->cacheFactory->getCache(CacheNames::VISIBLE_FOLDERS_FOR_USER);
        return $cache->load($userId, function() use ($userId) {
            $groupIds = $this->gr->getGroupsForUser($userId);

            $folderIds = [];
            foreach($groupIds as $groupId) {
                $folderIdsTmp = $this->fr->getVisibleFolderIdsForGroup($groupId);

                foreach($folderIdsTmp as $folderId) {
                    if(!in_array($folderId, $folderIds)) {
                        $folderIds[] = $folderId;
                    }
                }
            }

            $folders = [];
            foreach($folderIds as $folderId) {
                $folder = $this->fr->getFolderById($folderId);
                $folder = DatabaseRow::createFromDbRow($folder);

                $folders[] = $folder;
            }

            return $folders;
        });
    }
}

?>