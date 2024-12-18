<?php

namespace App\Lib\Processes\Shredding;

use App\Constants\Container\DocumentStatus;
use App\Constants\Container\SystemProcessTypes;
use App\Exceptions\AException;
use App\Exceptions\GeneralException;
use App\Lib\Processes\ADocumentBulkProcess;

class ShreddingProcess extends ADocumentBulkProcess {
    private ?AException $finalExecuteException = null;

    public function canExecute(array $documentIds, ?string $userId = null, array &$exceptions = []): bool {
        return $this->internalCheckCanExecute('canExecuteShredding', $documentIds, $userId, $exceptions);
    }

    public function execute(array $documentIds, ?string $userId = null, array &$exceptions = []): bool {
        foreach($documentIds as $documentId) {
            try {
                $this->documentBulkActionAuthorizator->throwExceptionIfCannotExecuteShredding($userId ?? $this->currentUser->getId(), $documentId);

                if($userId === null) {
                    $userId = $this->currentUser->getId();
                }

                if(!$this->processManager->saveProcess($documentId, SystemProcessTypes::SHREDDING, $userId, $userId, [$userId])) {
                    throw new GeneralException('Database error.');
                }

                if(!$this->finalExecute($documentId, $userId)) {
                    $text = 'Could not shred document.';

                    if($this->finalExecuteException !== null) {
                        $text .= ' Reason: ' . $this->finalExecuteException->getMessage();
                    }

                    throw new GeneralException($text);
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

        $result = true;

        try {
            $this->documentManager->dr->beginTransaction(__METHOD__);

            $this->documentManager->updateDocument($documentId, $data);

            $this->documentManager->dr->commit($userId ?? $this->currentUser->getId(), __METHOD__);
        } catch(AException $e) {
            $this->documentManager->dr->rollback(__METHOD__);

            $this->finalExecuteException = $e;

            $result = false;
        }

        return $result;
    }
}

?>