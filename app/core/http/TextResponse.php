<?php

namespace App\Core\Http;

/**
 * Response that is used for sending string or text as response
 * 
 * @author Lukas Velek
 */
class TextResponse extends AResponse {
    public function __construct(string $data) {
        parent::__construct($data);
    }

    public function getResult(): string {
        return $this->data;
    }
}

?>