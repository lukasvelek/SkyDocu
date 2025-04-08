<?php

namespace App\Api\Documents;

use App\Api\ACreateAPIOperation;
use App\Constants\Container\DocumentStatus;
use App\Constants\Container\ExternalSystemLogObjectTypes;
use App\Constants\Container\ExternalSystemRightsOperations;
use App\Core\Http\JsonResponse;

class CreateDocumentsController extends ACreateAPIOperation {
    protected function run(): JsonResponse {
        if(!$this->checkRight(ExternalSystemRightsOperations::CREATE_DOCUMENTS)) {
            return new JsonResponse(['error' => 'Operation is not allowed.']);
        }

        $this->setProperties([
            'title',
            'classId',
            'authorUserId',
            'folderId',
            'status',
            ':description',
            ':fileId'
        ]);

        $result = $this->createDocument();

        $this->logCreate(ExternalSystemLogObjectTypes::DOCUMENT);

        return new JsonResponse(['documentId' => $result]);
    }

    /**
     * Creates a new document
     * 
     * @return string Document ID
     */
    private function createDocument(): string {
        $data = [
            'title' => $this->params['title'],
            'classId' => $this->params['classId'],
            'authorUserId' => $this->params['authorUserId'],
            'folderId' => $this->params['folderId'],
            'status' => DocumentStatus::NEW
        ];

        if($this->params['description'] !== null) {
            $data['description'] = $this->params['description'];
        }

        $documentId = $this->container->documentManager->createNewDocument($data, []);

        if($this->params['fileId'] !== null) {
            $this->container->fileStorageManager->createNewFileDocumentRelation($documentId, $this->params['fileId']);
        }

        return $documentId;
    }
}

?>