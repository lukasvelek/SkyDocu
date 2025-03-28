<?php

namespace App\Api\Documents;

use App\Api\AAuthenticatedApiController;
use App\Constants\Container\ExternalSystemLogObjectTypes;
use App\Core\DB\DatabaseRow;
use App\Core\Http\JsonResponse;
use App\Exceptions\GeneralException;

class GetDocumentsController extends AAuthenticatedApiController {
    protected function run(): JsonResponse {
        $results = [];
        $properties = $this->get('properties');

        if(array_key_exists('documentId', $this->data)) {
            // single
            $document = $this->getDocument($this->get('documentId'));

            foreach($properties as $property) {
                if(!$this->checkProperty($property)) continue;

                $results[$property] = $document->$property;
            }

            $this->logRead(false, ExternalSystemLogObjectTypes::DOCUMENT);
        } else {
            // multiple
            $documents = $this->getDocuments($this->get('limit'), $this->get('offset'));

            foreach($documents as $document) {
                foreach($properties as $property) {
                    if(!$this->checkProperty($property)) continue;
                    
                    $results[$document->documentId][$property] = $document->$property;
                }
            }

            $this->logRead(true, ExternalSystemLogObjectTypes::DOCUMENT);
        }

        return new JsonResponse(['data' => $results]);
    }

    /**
     * Checks if property is enabled
     * 
     * @param string $name Property name
     */
    private function checkProperty(string $name): bool {
        return in_array($name, [
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
    }
    
    /**
     * Returns a document
     * 
     * @param string $documentId Document ID
     */
    private function getDocument(string $documentId): DatabaseRow {
        $document = $this->container->documentRepository->getDocumentById($documentId);

        if($document === null) {
            throw new GeneralException('Document does not exist.');
        }

        return DatabaseRow::createFromDbRow($document);
    }

    /**
     * Returns an array of documents
     * 
     * @param int $limit Limit
     * @param int $offset Offset
     */
    private function getDocuments(int $limit, int $offset): array {
        $qb = $this->container->documentRepository->composeQueryForDocuments()
            ->limit($limit)
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