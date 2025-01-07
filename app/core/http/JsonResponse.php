<?php

namespace App\Core\Http;

/**
 * Response that is used for sending JSON as response
 * 
 * @author Lukas Velek
 */
class JsonResponse extends AResponse {
    public function __construct(array $data) {
        parent::__construct($data);
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