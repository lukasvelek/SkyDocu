<?php

namespace App\Lib\Processes\Shredding;

use App\Constants\Container\SystemProcessTypes;
use App\Exceptions\AException;
use App\Lib\Processes\ADocumentBulkProcess;

class ShreddingRequestProcess extends ADocumentBulkProcess {
    public function canExecute(array $documentIds, ?string $userId = null, array &$exceptions = []): bool {
        $execute = true;

        foreach($documentIds as $documentId) {
            if($execute === false) {
                break;
            }

            $execute = $this->documentBulkActionAuthorizator->canExecuteShreddingRequest($userId ?? $this->currentUser->getId(), $documentId);
        }

        return $execute;
    }

    public function execute(array $documentIds, ?string $userId = null, array &$exceptions = []): bool {
        foreach($documentIds as $documentId) {
            try {
                $this->documentBulkActionAuthorizator->throwExceptionIfCannotExecuteShreddingRequest($userId ?? $this->currentUser->getId(), $documentId);
                
                $workflow = $this->createWorkflowForDocument($documentId, $userId ?? $this->currentUser->getId());

                $this->processManager->startProcess($documentId, SystemProcessTypes::SHREDDING_REQUEST, $userId ?? $this->currentUser->getId(), $workflow[0], $workflow);
            } catch(AException $e) {
                $exceptions[] = $e;
            }
        }

        return $this->evaluateResult($documentIds, $exceptions);
    }

    private function createWorkflowForDocument(string $documentId, string $userId) {
        $workflow = [];

        $document = $this->documentManager->getDocumentById($documentId);

        $workflow[] = $document->authorUserId;

        if($document->authorUserId != $userId) {
            $workflow[] = $document->authorUserId;
        }

        return $workflow;
    }
}

?>