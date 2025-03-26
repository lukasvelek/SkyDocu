<?php

namespace App\Api\Documents;

use App\Api\ABaseApiClass;
use App\Core\DB\DatabaseRow;
use App\Core\Http\JsonResponse;
use App\Exceptions\AException;
use App\Exceptions\ApiException;
use App\Repositories\Container\DocumentRepository;

/**
 * GetDocuments controller
 * 
 * @author Lukas Velek
 */
class GetDocumentsController extends ABaseApiClass {
    public function run(): JsonResponse {
        try {
            $this->startup();

            $this->tokenAuth();

            $documentId = $this->getDocumentId();

            $containerId = $this->getContainerId();

            $container = $this->app->containerManager->getContainerById($containerId, true);

            $conn = $this->app->dbManager->getConnectionToDatabase($container->getDefaultDatabase()->getName());

            $documentRepository = new DocumentRepository($conn, $this->app->logger);

            $document = $documentRepository->getDocumentById($documentId);

            if($document === null) {
                throw new ApiException('No document found.');
            }

            $document = DatabaseRow::createFromDbRow($document);

            $properties = $this->getProperties();

            $results = [];
            foreach($properties as $property) {
                $results[$property] = $document->$property;
            }

            return new JsonResponse(['data' => $results]);
        } catch(AException $e) {
            return $this->convertExceptionToJson($e);
        }
    }

    /**
     * Returns document ID
     */
    private function getDocumentId(): string {
        $documentId = $this->get('documentId');

        if($documentId === null) {
            throw new ApiException('No document ID entered.');
        }

        return $documentId;
    }

    /**
     * Returns properties
     */
    private function getProperties(): array {
        $properties = $this->get('properties');

        if($properties === null || empty($properties)) {
            throw new ApiException('No properties entered.');
        }

        return $properties;
    }
}

?>