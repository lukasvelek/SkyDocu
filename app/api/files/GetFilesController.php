<?php

namespace App\Api\Files;

use App\Api\AReadAPIOperation;
use App\Constants\Container\ExternalSystemLogObjectTypes;
use App\Core\DB\DatabaseRow;
use App\Core\Http\JsonResponse;

class GetFilesController extends AReadAPIOperation {
    protected function run(): JsonResponse {
        $this->setAllowedProperties([
            'fileId',
            'filename',
            'filepath',
            'filesize',
            'userId',
            'hash',
            'dateCreated'
        ]);

        $results = $this->getResults([$this, 'getFiles'], 'fileId', $this->get('limit'), $this->get('offset'));

        $this->logRead(ExternalSystemLogObjectTypes::FILES);

        return new JsonResponse(['data' => $results]);
    }

    protected function getFiles(int $limit, int $offset): array {
        $qb = $this->container->fileStorageRepository->composeQueryForStoredFiles();

        $this->appendWhereConditions($qb);

        $qb->limit($limit)
            ->offset($offset)
            ->execute();

        $files = [];
        while($row = $qb->fetchAssoc()) {
            $files[] = DatabaseRow::createFromDbRow($row);
        }

        return $files;
    }
}

?>