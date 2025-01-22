<?php

namespace App\Core\Http;

/**
 * Response that is used for sending JSON Error response
 * 
 * @author Lukas Velek
 */
class JsonErrorResponse extends AResponse {
    public function __construct(string $errorMessage) {
        parent::__construct(['error' => '1', 'errorMsg' => $errorMessage]);
    }

    public function getResult(): string {
        $result = json_encode($this->data);

        if($result === false) {
            return '';
        }

        return $result;
    }
}

?>