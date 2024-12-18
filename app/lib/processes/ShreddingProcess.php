<?php

namespace App\Lib\Processes;

use App\Constants\Container\DocumentStatus;
use App\Exceptions\AException;
use App\Exceptions\GeneralException;

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
        foreach($documentIds as $documentId) {
            try {
                $this->documentBulkActionAuthorizator->throwExceptionIfCannotExecuteShredding($userId ?? $this->currentUser->getId(), $documentId);

                if(!$this->finalExecute($documentId, $userId)) {
                    throw new GeneralException('Could not shred document.');
                }
            } catch(AException $e) {
                $exceptions[] = $e;
            }
        }

        return $this->evaluateResult($documentIds, $exceptions);
    }

    public function finalExecute(string $documentId, ?string $userId = null): bool {
        $data = [
            'status' => DocumentStatus::SHREDDED
        ];

        $this->documentManager->updateDocument($documentId, $data);

        return true;
    }
}

?>