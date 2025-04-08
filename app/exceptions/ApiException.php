<?php

namespace App\Exceptions;

use Throwable;

class ApiException extends AException {
    public function __construct(string $message, ?Throwable $previous = null) {
        parent::__construct('ApiException', $message, $previous);
    }
}

?>