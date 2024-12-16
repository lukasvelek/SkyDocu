<?php

namespace App\Helpers;

use App\Core\DB\DatabaseRow;

/**
 * ProcessHelper contains useful methods for working with processes
 * 
 * @author Lukas Velek
 */
class ProcessHelper {
    /**
     * Checks if given user is present in workflow of given process.
     * 
     * @param string $userId User ID
     * @param DatabaseRow $processRow
     * @return bool True if user is present in workflow or false if not
     */
    public static function isUserInProcessWorkflow(string $userId, DatabaseRow $processRow) {
        $workflowUsers = self::convertWorkflowFromDb($processRow);

        return in_array($userId, $workflowUsers);
    }

    /**
     * Converts process workflow users array to form for saving to the database
     * 
     * @param array $workflowUsers Workflow users array
     * @return string Workflow users
     */
    public static function convertWorkflowToDb(array $workflowUsers) {
        return implode(';', $workflowUsers);
    }

    /**
     * Converts process workflow users from database form to an array
     * 
     * @param DatabaseRow $processRow Process database row
     * @return array Workflow users array
     */
    public static function convertWorkflowFromDb(DatabaseRow $processRow) {
        $workflowUsers = $processRow->workflowUserIds;

        return explode(';', $workflowUsers);
    }
}

?>