<?php

namespace App\Lib\Processes;

class ShreddingProcess extends ADocumentBulkProcess {
    public function canExecute(array $documentIds, ?string $userId = null, array &$exceptions = []): bool {
        return true;
    }

    public function execute(array $documentIds, ?string $userId = null, array &$exceptions = []): bool {
        return true;
    }
}

?>