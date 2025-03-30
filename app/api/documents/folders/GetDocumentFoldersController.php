<?php

namespace App\Api\Documents\Folders;

use App\Api\AReadAPIOperation;
use App\Constants\Container\ExternalSystemLogObjectTypes;
use App\Core\DB\DatabaseRow;
use App\Core\Http\JsonResponse;

class GetDocumentFoldersController extends AReadAPIOperation {
    protected function run(): JsonResponse {
        $this->setAllowedProperties([
            'folderId',
            'title',
            'isSystem',
            'parentFolderId'
        ]);

        $results = [];
        $properties = $this->processPropeties($this->get('properties'));

        $folders = $this->getFolders($this->get('limit'), $this->get('offset'));

        foreach($folders as $folder) {
            foreach($properties as $property) {
                $results[$folder->folderId][$property] = $folder->$property;
            }
        }

        $this->logRead(ExternalSystemLogObjectTypes::DOCUMENT_FOLDERS);

        return new JsonResponse(['data' => $results]);
    }

    private function getFolders(int $limit, int $offset): array {
        $qb = $this->container->folderRepository->composeQueryForFolders();

        $this->appendWhereConditions($qb);

        $qb->limit($limit)
            ->offset($offset)
            ->execute();

        $folders = [];
        while($row = $qb->fetchAssoc()) {
            $folders[] = DatabaseRow::createFromDbRow($row);
        }

        return $folders;
    }
}

?>