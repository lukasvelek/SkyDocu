<?php

namespace App\Lib\Processes\Shredding;

use App\Constants\Container\DocumentStatus;
use App\Constants\Container\SystemProcessTypes;
use App\Exceptions\AException;
use App\Lib\Processes\ADocumentBulkProcess;

/**
 * Shredding request document bulk process
 * 
 * @author Lukas Velek
 */
class ShreddingRequestProcess extends ADocumentBulkProcess {
    public function canExecute(array $documentIds, ?string $userId = null, array &$exceptions = []): bool {
        return $this->internalCheckCanExecute('canExecuteShreddingRequest', $documentIds, $userId, $exceptions);
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

    public function finalExecute(string $documentId, ?string $userId = null): bool {
        $data = [
            'status' => DocumentStatus::READY_FOR_SHREDDING
        ];

        $this->documentManager->updateDocument($documentId, $data);

        return true;
    }
}

?>