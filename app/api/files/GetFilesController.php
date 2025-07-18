<?php

namespace App\Api\Files;

use App\Api\AReadAPIOperation;
use App\Constants\Container\ExternalSystemLogObjectTypes;
use App\Constants\Container\ExternalSystemRightsOperations;
use App\Core\DB\DatabaseRow;
use App\Core\Http\JsonResponse;

class GetFilesController extends AReadAPIOperation {
    protected function run(): JsonResponse {
        if(!$this->checkRight(ExternalSystemRightsOperations::READ_FILES)) {
            return new JsonResponse(['error' => 'Operation is not allowed.']);
        }

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
        $qb = $this->app->fileStorageRepository->composeQueryForFilesInStorage($this->containerId);

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