<?php

namespace App\Exceptions;

use Throwable;

class BadCredentialsException extends AException {
    public function __construct(?string $userId, ?string $email, string $processName = 'authentication', ?Throwable $previous = null) {
        $userInfo = '';

        if($userId === null && $email === null) {
            throw new RequiredAttributeIsNotSetException('userId or email', 'BadCredentialsException', $previous);
        }

        if($userId !== null) {
            $userInfo = '#' . $userId;
        } else if($email !== null) {
            $userInfo = $email;
        }

        parent::__construct('BadCredentialsException', 'User ' . $userInfo . ' has entered bad credentials during ' . $processName . '.', $previous);
    }
}

?>