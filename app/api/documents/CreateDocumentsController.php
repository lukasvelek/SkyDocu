<?php

namespace App\Api\Documents;

use App\Api\AAuthenticatedApiController;
use App\Constants\Container\DocumentStatus;
use App\Core\Http\JsonResponse;
use App\Exceptions\GeneralException;
use App\Managers\EntityManager;
use App\Repositories\Container\DocumentRepository;
use App\Repositories\ContentRepository;

class CreateDocumentsController extends AAuthenticatedApiController {
    protected function run(): JsonResponse {
        $description = null;
        if(array_key_exists('description', $this->data)) {
            $description = $this->get('description');
        }

        $result = $this->createDocument($this->get('title'), $this->get('classId'), $this->get('authorUserId'), $this->get('folderId'), $description);

        return new JsonResponse(['documentId' => $result]);
    }

    /**
     * Creates a new document
     * 
     * @param string $title Title
     * @param string $classId Class ID
     * @param string $authorUserId Author User ID
     * @param string $folderId Folder ID
     * @param ?string $description Description or null
     * @return string Document ID
     */
    private function createDocument(string $title, string $classId, string $authorUserId, string $folderId, ?string $description): string {
        $contentRepository = new ContentRepository($this->conn, $this->app->logger);
        $entityManager = new EntityManager($this->app->logger, $contentRepository);

        $documentId = $entityManager->generateEntityId(EntityManager::C_DOCUMENTS);

        $documentRepository = new DocumentRepository($this->conn, $this->app->logger);

        $data = [
            'title' => $title,
            'classId' => $classId,
            'authorUserId' => $authorUserId,
            'folderId' => $folderId,
            'status' => DocumentStatus::NEW
        ];

        if($description !== null) {
            $data['description'] = $description;
        }

        if(!$documentRepository->createNewDocument($documentId, $data)) {
            throw new GeneralException('Could not create a new document.');
        }

        return $documentId;
    }
}

?>