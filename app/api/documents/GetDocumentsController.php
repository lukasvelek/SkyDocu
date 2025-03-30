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

        $results = [];
        $properties = $this->processPropeties($this->get('properties'));

        $documents = $this->getDocuments($this->get('limit'), $this->get('offset'));

        foreach($documents as $document) {
            foreach($properties as $property) {
                $results[$document->documentId][$property] = $document->$property;
            }
        }

        $this->logRead(ExternalSystemLogObjectTypes::DOCUMENT);

        return new JsonResponse(['data' => $results]);
    }

    /**
     * Returns an array of documents
     * 
     * @param int $limit Limit
     * @param int $offset Offset
     */
    private function getDocuments(int $limit, int $offset): array {
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