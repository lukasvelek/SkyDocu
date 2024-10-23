<?php

namespace App\Exceptions;

use Throwable;

class UndefinedConstantException extends AException {
    public function __construct(string $name, ?Throwable $previous = null) {
        $message = '\'' . $name . '\' is not defined.';
        parent::__construct('UndefinedConstantException', $message, $previous);
    }
}

?>