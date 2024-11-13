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
}

?>