<?php

namespace App\Lib\Processes;

class ShreddingProcess extends ADocumentBulkProcess {
    public function canExecute(array $documentIds, ?string $userId = null, array &$exceptions = []): bool {
        $execute = true;

        foreach($documentIds as $documentId) {
            if($execute === false) {
                break;
            }

            $execute = $this->documentBulkActionAuthorizator->canExecuteShredding($userId ?? $this->currentUser->getId(), $documentId);
        }

        return $execute;
    }

    public function execute(array $documentIds, ?string $userId = null, array &$exceptions = []): bool {
        return true;
    }

    public function finalExecute(string $documentId, ?string $userId = null): bool {
        return true;
    }
}

?>