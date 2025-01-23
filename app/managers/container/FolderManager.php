<?php

namespace App\Managers\Container;

use App\Constants\Container\SystemGroups;
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
    private FolderRepository $folderRepository;
    private GroupRepository $groupRepository;

    public function __construct(Logger $logger, EntityManager $entityManager, FolderRepository $folderRepository, GroupRepository $groupRepository) {
        parent::__construct($logger, $entityManager);

        $this->folderRepository = $folderRepository;
        $this->groupRepository = $groupRepository;
    }

    public function getVisibleFolderIdsForGroup(string $groupId) {
        $cache = $this->cacheFactory->getCache(CacheNames::VISIBLE_FOLDER_IDS_FOR_GROUP);
        return $cache->load($groupId, function() use ($groupId) {
            return $this->folderRepository->getVisibleFolderIdsForGroup($groupId);
        });
    }

    public function getVisibleFoldersForUser(string $userId) {
        $cache = $this->cacheFactory->getCache(CacheNames::VISIBLE_FOLDERS_FOR_USER);
        return $cache->load($userId, function() use ($userId) {
            $groupIds = $this->groupRepository->getGroupsForUser($userId);

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
                $folder = $this->folderRepository->getFolderById($folderId);
                $folder = DatabaseRow::createFromDbRow($folder);

                $folders[] = $folder;
            }

            return $folders;
        });
    }

    public function composeQueryForVisibleFoldersForUser(string $userId) {
        $groupIds = $this->groupRepository->getGroupsForUser($userId);

        $folderIds = [];
        foreach($groupIds as $groupId) {
            $folderIdsTmp = $this->getVisibleFolderIdsForGroup($groupId);

            foreach($folderIdsTmp as $folderId) {
                if(!in_array($folderId, $folderIds)) {
                    $folderIds[] = $folderId;
                }
            }
        }

        $qb = $this->folderRepository->composeQueryForFolders();

        $qb->where($qb->getColumnInValues('folderId', $folderIds))
            ->orderBy('title');

        return $qb;
    }

    public function createNewFolder(string $title, string $callingUserId, ?string $parentFolderId = null) {
        $folderId = $this->createId(EntityManager::C_DOCUMENT_FOLDERS);

        if(!$this->folderRepository->createNewFolder($folderId, $title, $parentFolderId)) {
            throw new GeneralException('Database error.');
        }

        if($parentFolderId === null) {
            $groupIds = $this->groupRepository->getGroupsForUser($callingUserId);
            $administratorsGroup = $this->groupRepository->getGroupByTitle(SystemGroups::ADMINISTRATORS);

            foreach($groupIds as $groupId) {
                if($administratorsGroup !== null && $administratorsGroup['groupId'] == $groupId) {
                    $this->updateGroupFolderRight($folderId, $groupId, true, true, true, true);
                } else {
                    $this->updateGroupFolderRight($folderId, $groupId, true, true, false, false);
                }
            }
        }

        if(!$this->cacheFactory->invalidateCacheByNamespace(CacheNames::FOLDER_SUBFOLDERS_MAPPING) ||
            !$this->cacheFactory->invalidateCacheByNamespace(CacheNames::VISIBLE_FOLDER_IDS_FOR_GROUP) ||
            !$this->cacheFactory->invalidateCacheByNamespace(CacheNames::VISIBLE_FOLDERS_FOR_USER)) {
            throw new GeneralException('Could not invalidate cache.');
        }
    }

    public function updateGroupFolderRight(string $folderId, string $groupId, bool $canView = true, bool $canCreate = false, bool $canEdit = false, bool $canDelete = false) {
        $relationId = $this->folderRepository->getGroupFolderRelationId($groupId, $folderId);

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
            $result = $this->folderRepository->insertGroupFolderRelation($relationId, $data);
        } else {
            // update
            $result = $this->folderRepository->updateGroupFolderRelation($relationId, $data);
        }

        if(!$result) {
            throw new GeneralException('Database error.');
        }

        if(!$this->cacheFactory->invalidateCacheByNamespace(CacheNames::VISIBLE_FOLDER_IDS_FOR_GROUP) ||
            !$this->cacheFactory->invalidateCacheByNamespace(CacheNames::VISIBLE_FOLDERS_FOR_USER)) {
            throw new GeneralException('Could not invalidate cache.');
        }
    }

    public function deleteGroupFolderRight(string $folderId, string $groupId) {
        if(!$this->folderRepository->removeGroupFolderRelation($folderId, $groupId)) {
            throw new GeneralException('Database error.');
        }

        if(!$this->cacheFactory->invalidateCacheByNamespace(CacheNames::VISIBLE_FOLDER_IDS_FOR_GROUP) ||
            !$this->cacheFactory->invalidateCacheByNamespace(CacheNames::VISIBLE_FOLDERS_FOR_USER)) {
            throw new GeneralException('Could not invalidate cache.');
        }
    }

    public function getFolderById(string $folderId) {
        $folder = $this->folderRepository->getFolderById($folderId);

        if($folder === null) {
            throw new NonExistingEntityException('Folder does not exist.');
        }

        return DatabaseRow::createFromDbRow($folder);
    }

    public function getDefaultFolder() {
        $qb = $this->folderRepository->composeQueryForFolders();
        $folder = $qb->andWhere('isSystem = 1')
            ->andWhere('title = ?', ['Default'])
            ->execute()
            ->fetch();

        if($folder === null) {
            throw new NonExistingEntityException('Default folder does not exist.');
        }

        return DatabaseRow::createFromDbRow($folder);
    }

    public function getSubfoldersForFolder(string $folderId, bool $recursive = false) {
        $cache = $this->cacheFactory->getCache(CacheNames::FOLDER_SUBFOLDERS_MAPPING);
        
        return $cache->load($folderId, function() use ($folderId) {
            $qb = $this->composeQueryForSubfoldersForFolder($folderId);
            $qb->execute();

            $subfolders = [];
            while($row = $qb->fetchAssoc()) {
                $row = DatabaseRow::createFromDbRow($row);

                $subfolders[] = $row;
            }

            return $subfolders;
        });
    }

    public function composeQueryForSubfoldersForFolder(string $folderId) {
        $qb = $this->folderRepository->composeQueryForFolders();
        $qb->andWhere('parentFolderId = ?', [$folderId])
            ->orderBy('title');

        return $qb;
    }

    /**
     * Returns all folders that the given folder is subfolder in
     */
    public function getFolderPathToRoot(string $folderId) {
        $folders = [];
        
        $run = true;

        $folder = $this->getFolderById($folderId);
        
        $folderIds = [];
        $folderObjs = [];
        do {
            if($folder->parentFolderId === null) {
                $run = false;
            }
            array_unshift($folderIds, $folder->folderId);
            $folderObjs[$folder->folderId] = $folder;
            
            if($folder->parentFolderId !== null) {
                $folder = $this->getFolderById($folder->parentFolderId);
            }
        } while($run === true);

        foreach($folderIds as $folderId) {
            $folders[] = $folderObjs[$folderId];
        }

        return $folders;
    }
}

?>