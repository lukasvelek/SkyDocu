<?php

namespace App\Lib\Processes;

use App\Constants\Container\DocumentStatus;
use App\Constants\Container\SystemProcessTypes;
use App\Exceptions\AException;
use App\Exceptions\GeneralException;

/**
 * Archiving document bulk process
 * 
 * @author Lukas Velek
 */
class ArchivingProcess extends ADocumentBulkProcess {
    private ?AException $finalExecuteException = null;

    public function canExecute(array $documentIds, ?string $userId = null, array &$exceptions = []): bool {
        return $this->internalCheckCanExecute('canExecuteArchivation', $documentIds, $userId, $exceptions);
    }

    public function execute(array $documentIds, ?string $userId = null, array &$exceptions = []): bool {
        foreach($documentIds as $documentId) {
            try {
                $this->documentBulkActionAuthorizator->throwExceptionIfCannotExecuteArchivation($userId ?? $this->currentUser->getId(), $documentId);

                if($userId === null) {
                    $userId = $this->currentUser->getId();
                }

                if(!$this->processManager->saveProcess($documentId, SystemProcessTypes::ARCHIVATION, $userId, $userId, [$userId])) {
                    throw new GeneralException('Database error.');
                }

                if(!$this->finalExecute($documentId, $userId)) {
                    $text = 'Could not archive document.';

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
            'status' => DocumentStatus::ARCHIVED
        ];

        $result = true;

        try {
            $this->documentManager->documentRepository->beginTransaction(__METHOD__);

            $this->documentManager->updateDocument($documentId, $data);

            $this->documentManager->documentRepository->commit($userId ?? $this->currentUser->getId(), __METHOD__);
        } catch(AException $e) {
            $this->documentManager->documentRepository->rollback(__METHOD__);

            $this->finalExecuteException = $e;

            $result = false;
        }

        return $result;
    }
}

?>