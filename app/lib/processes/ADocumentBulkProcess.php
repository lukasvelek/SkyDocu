<?php

namespace App\Lib\Processes;

/**
 * Common predecessor to all processes available for document bulks
 * 
 * @author Lukas Velek
 */
abstract class ADocumentBulkProcess extends AProcess {
    /**
     * Tests if current operation is executable on given array of document IDs for given user (or current user). This is useful for testing for bulk actions.
     * 
     * @param array $documentIds Array of Document IDs
     * @param ?string $userId User ID or null if current user should be used
     * @param array $exceptions Caught exceptions
     * @return bool True if operation is executable on all of the documents or false if not
     */
    public abstract function canExecute(array $documentIds, ?string $userId = null, array &$exceptions = []): bool;
}

?>