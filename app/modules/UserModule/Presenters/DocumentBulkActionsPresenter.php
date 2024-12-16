<?php

namespace App\Modules\UserModule;

use App\Constants\Container\DocumentStatus;
use App\Exceptions\AException;
use App\Modules\AContainerPresenter;

class DocumentBulkActionsPresenter extends AContainerPresenter {
    public function __construct() {
        parent::__construct('DocumentBulkActionsPresenter', 'Document bulk actions');
    }

    public function handleArchiveDocuments() {
        $documentIds = $this->httpRequest->query['documentId'];

        $count = 0;
        foreach($documentIds as $documentId) {
            try {
                $this->documentRepository->beginTransaction(__METHOD__);

                $this->documentManager->updateDocument($documentId, [
                    'status' => DocumentStatus::ARCHIVED
                ]);

                $this->documentRepository->commit($this->getUserId(), __METHOD__);
                $count++;
            } catch(AException $e) {
                $this->documentRepository->rollback(__METHOD__);
            }
        }

        if($count == count($documentIds)) {
            $this->flashMessage('Documents archived.', 'success');
        } else if($count == 0) {
            $this->flashMessage('Some documents archived.', 'success');
            $this->flashMessage('Some documents could not be archived.', 'error', 10);
        } else {
            $this->flashMessage('Could not archive any documents.', 'error', 10);
        }

        $this->redirect($this->createBackUrlFromUrl());
    }

    private function createBackUrlFromUrl() {
        $page = $this->httpRequest->query['backPage'];
        $action = $this->httpRequest->query['backAction'];

        return $this->createFullURL($page, $action);
    }

    public function handleStartProcess() {
        $process = $this->httpGet('process', true);
        $documentIds = $this->httpRequest->query['documentId'];

        $exceptions = [];

        try {
            $result = $this->processFactory->startDocumentProcess($process, $documentIds, $exceptions);

            if(!empty($exceptions)) {
                /**
                 * @var AException $exception
                 */
                foreach($exceptions as $exception) {
                    $this->flashMessage('Error during process: ' . $exception->getMessage(), 'error', 10);
                }
            }

            if($result === true && empty($exception)) {
                $this->flashMessage('Process run successfully.', 'success');
            } else {
                $this->flashMessage('An error occurred while running process.', 'error', 10);
            }
        } catch(AException $e) {
            $this->flashMessage('An error occurred while running process. Reason: ' . $e->getMessage(), 'error', 10);
        }

        $backPage = $this->httpGet('backPage');
        $backAction = $this->httpGet('backAction');

        $backUrl = [];
        if($backPage !== null && $backAction !== null) {
            $backUrl = ['page' => $backPage, 'action' => $backAction];
        } else {
            $backUrl = $this->createFullURL('User:Documents', 'list');
        }

        $this->redirect($backUrl);
    }
}

?>