<?php

namespace App\Api\Documents;

use App\Api\AReadAPIOperation;
use App\Constants\Container\ExternalSystemLogObjectTypes;
use App\Core\DB\DatabaseRow;
use App\Core\Http\JsonResponse;

class GetDocumentsController extends AReadAPIOperation {
    protected function run(): JsonResponse {
        $this->setAllowedProperties([
            'documentId',
            'title',
            'authorUserId',
            'description',
            'status',
            'classId',
            'folderId',
            'dateCreated',
            'dateModified'
        ]);

        $results = $this->getResults([$this, 'getDocuments'], 'documentId', $this->get('limit'), $this->get('offset'));

        $this->logRead(ExternalSystemLogObjectTypes::DOCUMENT);

        return new JsonResponse(['data' => $results]);
    }

    /**
     * Returns an array of documents
     * 
     * @param int $limit Limit
     * @param int $offset Offset
     */
    protected function getDocuments(int $limit, int $offset): array {
        $qb = $this->container->documentRepository->composeQueryForDocuments();

        $this->appendWhereConditions($qb);

        $qb->limit($limit)
            ->offset($offset)
            ->execute();

        $documents = [];
        while($row = $qb->fetchAssoc()) {
            $documents[] = DatabaseRow::createFromDbRow($row);
        }

        return $documents;
    }
}

?>