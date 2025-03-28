<?php

namespace App\Api\Documents;

use App\Api\AAuthenticatedApiController;
use App\Core\DB\DatabaseRow;
use App\Core\Http\JsonResponse;
use App\Exceptions\GeneralException;
use App\Repositories\Container\DocumentRepository;

class GetDocumentsController extends AAuthenticatedApiController {
    protected function run(): JsonResponse {
        $results = [];
        $properties = $this->get('properties');

        if(array_key_exists('documentId', $this->data)) {
            // single
            $document = $this->getDocument($this->get('documentId'));

            foreach($properties as $property) {
                $results[$property] = $document->$property;
            }
        } else {
            // multiple
            $documents = $this->getDocuments($this->get('limit'), $this->get('offset'));

            foreach($documents as $document) {
                foreach($properties as $property) {
                    $results[$document->documentId][$property] = $document->$property;
                }
            }
        }

        return new JsonResponse(['data' => $results]);
    }
    
    /**
     * Returns a document
     * 
     * @param string $documentId Document ID
     */
    private function getDocument(string $documentId): DatabaseRow {
        $documentRepository = $this->getDocumentRepository();

        $document = $documentRepository->getDocumentById($documentId);

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
        $documentRepository = $this->getDocumentRepository();

        $qb = $documentRepository->composeQueryForDocuments()
            ->limit($limit)
            ->offset($offset)
            ->execute();

        $documents = [];
        while($row = $qb->fetchAssoc()) {
            $documents[] = DatabaseRow::createFromDbRow($row);
        }

        return $documents;
    }

    /**
     * Returns an instance of DocumentRepository
     */
    private function getDocumentRepository(): DocumentRepository {
        return new DocumentRepository($this->conn, $this->app->logger);
    }
}

?>