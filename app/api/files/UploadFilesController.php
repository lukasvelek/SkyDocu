<?php

namespace App\Api\Files;

use App\Api\ACreateAPIOperation;
use App\Constants\Container\ExternalSystemLogObjectTypes;
use App\Constants\Container\ExternalSystemRightsOperations;
use App\Core\FileUploadManager;
use App\Core\Http\JsonResponse;

class UploadFilesController extends ACreateAPIOperation {
    protected function run(): JsonResponse {
        if(!$this->checkRight(ExternalSystemRightsOperations::UPLOAD_FILES)) {
            return new JsonResponse(['error' => 'Operation is not allowed.']);
        }

        $this->setProperties([
            'name',
            'content',
            ':documentId',
            ':userId'
        ]);

        $fileId = $this->createFile();

        $this->logCreate(ExternalSystemLogObjectTypes::FILES);

        return new JsonResponse(['data' => [
            'fileId' => $fileId
        ]]);
    }

    private function createFile() {
        $documentId = $this->params['documentId'];
        $userId = $this->params['userId'] ?? $this->app->userManager->getServiceUserId();

        $fup = new FileUploadManager();

        $data = $fup->createFile($this->params['name'], $this->params['content'], $documentId, $userId);

        $fileId = $this->container->fileStorageManager->createNewFile(
            $documentId,
            $userId,
            $data[FileUploadManager::FILE_FILENAME],
            $data[FileUploadManager::FILE_FILEPATH],
            $data[FileUploadManager::FILE_FILESIZE]
        );

        return $fileId;
    }
}

?>