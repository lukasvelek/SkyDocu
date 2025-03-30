<?php

namespace App\Api\Archive\Folders;

use App\Api\AReadAPIOperation;
use App\Constants\Container\ExternalSystemLogObjectTypes;
use App\Core\DB\DatabaseRow;
use App\Core\Http\JsonResponse;

class GetArchiveFoldersController extends AReadAPIOperation {
    protected function run(): JsonResponse {
        $this->setAllowedProperties([
            'folderId',
            'title',
            'isSystem',
            'parentFolderId',
            'status'
        ]);

        $results = $this->getResults([$this, 'getFolders'], 'folderId', $this->get('limit'), $this->get('offset'));

        $this->logRead(ExternalSystemLogObjectTypes::ARCHIVE_FOLDERS);

        return new JsonResponse(['data' => $results]);
    }

    protected function getFolders(int $limit, int $offset): array {
        $qb = $this->container->archiveRepository->composeQueryForArchiveFolders();

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