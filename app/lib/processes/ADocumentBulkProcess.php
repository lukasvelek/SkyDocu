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

    /**
     * Performs final execution of the process main operation. Usually called after workflow has been successfully finished.
     * 
     * @param string $documentId Document ID
     * @param ?string $userId User ID
     * @return bool True if operation was successful or false if not
     */
    public abstract function finalExecute(string $documentId, ?string $userId = null): bool;

    /**
     * Evaluates correct result for method 'execute' based on the number of thrown exceptions.
     * 
     * If there are no exceptions the result is true. If there are more documents than exceptions than the result is true.
     * If there are more exceptions than documents the result is false.
     * 
     * @param array $documentIds Document IDs
     * @param array $exceptions Exceptions
     * @return bool
     */
    protected function evaluateResult(array $documentIds, array $exceptions) {
        if(empty($exceptions)) {
            return true;
        } else {
            if(count($exceptions) < count($documentIds)) {
                return true;
            } else {
                return false;
            }
        }
    }
}

?>