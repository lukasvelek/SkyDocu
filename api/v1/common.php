<?php

/**
 * Converts exception to JSON to return to the API caller
 * 
 * @param Exception $e Exception thrown
 * @return string Exception converted to JSON
 */
function convertExceptionToJson(Exception $e): string {
    return json_encode([
        'error' => $e->getMessage()
    ]);
}

?>