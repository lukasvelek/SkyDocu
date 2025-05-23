<?php

namespace App\Exceptions;

use Throwable;

class NonExistingEntityException extends AException {
    public function __construct(string $message, ?Throwable $previous = null, bool $createFile = true) {
        parent::__construct('NonExistingEntityException', $message, $previous, $createFile);
    }
}

?>