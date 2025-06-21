<?php

namespace App\Helpers;

use App\Exceptions\GeneralException;

class ProcessEditorHelper {
    /**
     * Checks if service user definition schema is correct
     * 
     * @param array $data Definition schema
     * @throws GeneralException
     */
    public static function checkServiceUserDefinition(array $data) {
        if(!array_key_exists('name', $data)) {
            throw new GeneralException('No name is defined.');
        }
        if(!array_key_exists('operations', $data)) {
            throw new GeneralException('No operations are defined.');
        }

        /**
         * 
         * {
         *  "name": "test",
         *  "operations": {
         *   "status": 3
         *  }
         * }
         * 
         */

        $changeableValues = [
            'status',
            'instanceDescription'
        ];

        foreach($data['operations'] as $operation => $value) {
            if(!in_array($operation, $changeableValues)) {
                throw new GeneralException(sprintf('Unknown operation %s is defined.', $operation));
            }
        }
    }

    /**
     * Returns an array of update operations
     * 
     * @param array $data Definition
     */
    public static function getServiceUserDefinitionUpdateOperations(array $data): array {
        return $data['operations'];
    }
}

?>