<?php

namespace App\Exceptions;

use Throwable;

class RouterException extends AException {
    public function __construct(string $message, ?Throwable $previous = null, bool $createFile = false) {
        parent::__construct('RouterException', $message, $previous, $createFile);
    }
}

?>