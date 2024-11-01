<?php

namespace App\Managers\Container;

use App\Logger\Logger;
use App\Managers\AManager;
use App\Managers\EntityManager;
use App\Repositories\Container\DocumentClassRepository;
use App\Repositories\Container\DocumentRepository;
use App\Repositories\Container\FolderRepository;
use App\Repositories\Container\GroupRepository;

class DocumentManager extends AManager {
    private DocumentRepository $dr;
    private DocumentClassRepository $dcr;
    private GroupRepository $gr;
    private FolderRepository $fr;

    public function __construct(Logger $logger, EntityManager $entityManager, DocumentRepository $dr, DocumentClassRepository $dcr, GroupRepository $gr, FolderRepository $fr) {
        parent::__construct($logger, $entityManager);

        $this->dr = $dr;
        $this->dcr = $dcr;
        $this->gr = $gr;
        $this->fr = $fr;
    }

    public function composeQueryForDocuments(string $userId, string $folderId, bool $allMetadata) {
        $qb = $this->dr->composeQueryForDocuments();

        $qb->andWhere('folderId = ?', [$folderId]);

        $groupIds = $this->gr->getGroupsForUser($userId);

        $classes = [];
        foreach($groupIds as $groupId) {
            $classesTmp = $this->dcr->getVisibleClassesForGroup($groupId);

            foreach($classesTmp as $classId) {
                if(!in_array($classId, $classes)) {
                    $classes[] = $classId;
                }
            }
        }

        if(empty($classes)) {
            $qb->andWhere('1=0');
        } else {
            $qb->andWhere($qb->getColumnInValues('classId', $classes));
        }

        $sharedDocumentIds = $this->dr->getSharedDocumentsForUser($userId);
        
        if(!empty($sharedDocumentIds)) {
            $qb->orWhere($qb->getColumnInValues('documentId', $sharedDocumentIds));
        }

        if($allMetadata) {
            $visibleCustomMetadataIds = $this->fr->getVisibleCustomMetadataIdForFolder($folderId);

            $visibleCustomMetadata = [];
            foreach($visibleCustomMetadataIds as $metadataId) {
                $visibleCustomMetadata[] = $this->fr->getCustomMetadataById($metadataId);
            }
        }

        return $qb;
    }
}

?>