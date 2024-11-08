<?php

namespace App\Managers\Container;

use App\Core\Caching\CacheNames;
use App\Core\DB\DatabaseRow;
use App\Exceptions\GeneralException;
use App\Exceptions\NonExistingEntityException;
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

    public function getVisibleFolderIdsForGroup(string $groupId) {
        $cache = $this->cacheFactory->getCache(CacheNames::VISIBLE_FOLDER_IDS_FOR_GROUP);
        return $cache->load($groupId, function() use ($groupId) {
            return $this->fr->getVisibleFolderIdsForGroup($groupId);
        });
    }

    public function getVisibleFoldersForUser(string $userId) {
        $cache = $this->cacheFactory->getCache(CacheNames::VISIBLE_FOLDERS_FOR_USER);
        return $cache->load($userId, function() use ($userId) {
            $groupIds = $this->gr->getGroupsForUser($userId);

            $folderIds = [];
            foreach($groupIds as $groupId) {
                $folderIdsTmp = $this->getVisibleFolderIdsForGroup($groupId);

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

    public function composeQueryForVisibleFoldersForUser(string $userId) {
        $groupIds = $this->gr->getGroupsForUser($userId);

        $folderIds = [];
        foreach($groupIds as $groupId) {
            $folderIdsTmp = $this->getVisibleFolderIdsForGroup($groupId);

            foreach($folderIdsTmp as $folderId) {
                if(!in_array($folderId, $folderIds)) {
                    $folderIds[] = $folderId;
                }
            }
        }

        $qb = $this->fr->composeQueryForFolders();

        $qb->where($qb->getColumnInValues('folderId', $folderIds));

        return $qb;
    }

    public function createNewFolder(string $title, string $callingUserId) {
        $folderId = $this->createId(EntityManager::C_DOCUMENT_FOLDERS);

        if(!$this->fr->createNewFolder($folderId, $title)) {
            throw new GeneralException('Could not create new folder.');
        }

        $groupIds = $this->gr->getGroupsForUser($callingUserId);

        foreach($groupIds as $groupId) {
            $this->updateGroupFolderRight($folderId, $groupId, true, true, true, true);
        }
    }

    public function updateGroupFolderRight(string $folderId, string $groupId, bool $canView = true, bool $canCreate = false, bool $canEdit = false, bool $canDelete = false) {
        $relationId = $this->fr->getGroupFolderRelationId($groupId, $folderId);

        $data = [
            'canView' => $canView ? 1 : 0,
            'canCreate' => $canCreate ? 1 : 0,
            'canEdit' => $canEdit ? 1 : 0,
            'canDelete' => $canDelete ? 1 : 0
        ];

        $new = false;
        if($relationId === null) {
            // create
            $data['folderId'] = $folderId;
            $data['groupId'] = $groupId;

            $relationId = $this->createId(EntityManager::C_DOCUMENT_FOLDER_GROUP_RELATION);
            $new = true;
            $result = $this->fr->insertGroupFolderRelation($relationId, $data);
        } else {
            // update
            $result = $this->fr->updateGroupFolderRelation($relationId, $data);
        }

        if(!$result) {
            throw new GeneralException('Could not ' . ($new ? 'insert' : 'update') . ' group folder rights.');
        }

        $this->cacheFactory->invalidateCacheByNamespace(CacheNames::VISIBLE_FOLDER_IDS_FOR_GROUP);
        $this->cacheFactory->invalidateCacheByNamespace(CacheNames::VISIBLE_FOLDERS_FOR_USER);
    }

    public function getFolderById(string $folderId) {
        $folder = $this->fr->getFolderById($folderId);

        if($folder === null) {
            throw new NonExistingEntityException('Folder does not exist.');
        }

        return DatabaseRow::createFromDbRow($folder);
    }

    public function getGroupsWithoutRightsOnFolder(string $folderId) {
        $groups = $this->fr->composeQueryForGroupRightsInFolder($folderId);
    }
}

?>