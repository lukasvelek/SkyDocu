<?php

namespace App\Api\Documents;

use App\Api\AAuthenticatedApiController;
use App\Constants\Container\DocumentStatus;
use App\Constants\Container\ExternalSystemLogObjectTypes;
use App\Core\Http\JsonResponse;

class CreateDocumentsController extends AAuthenticatedApiController {
    protected function run(): JsonResponse {
        $description = null;
        if(array_key_exists('description', $this->data)) {
            $description = $this->get('description');
        }

        $result = $this->createDocument($this->get('title'), $this->get('classId'), $this->get('authorUserId'), $this->get('folderId'), $description);

        $this->logCreate(ExternalSystemLogObjectTypes::DOCUMENT);

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

        $documentId = $this->container->documentManager->createNewDocument($data, []);

        return $documentId;
    }
}

?>