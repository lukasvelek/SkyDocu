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

    /**
     * Checks if user is last user in process workflow
     * 
     * @param string $userId User ID
     * @param array $workflowUsers Workflow users
     * @return bool
     */
    public static function isUserLastInWorkflow(string $userId, array $workflowUsers) {
        $i = 0;
        foreach($workflowUsers as $user) {
            if($user == $userId) {
                break;
            }
        }

        return ($i + 1) == count($workflowUsers);
    }

    /**
     * Returns process instance data by JSON path
     * 
     * @param array $data Process instance data
     * @param string $jsonPath JSON path
     */
    public static function getInstanceDataByJsonPath(array $data, string $jsonPath) {
        $jsonPathParts = explode('.', $jsonPath);

        $d = $data;
        for($i = 0; $i < count($jsonPathParts); $i++) {
            $x = $jsonPathParts[$i];
            $d = $d[$x];
        }

        return $d;
    }
}

?>