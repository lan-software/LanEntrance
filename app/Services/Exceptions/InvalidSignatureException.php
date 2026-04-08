<?php

namespace App\Services\Exceptions;

class InvalidSignatureException extends TokenVerificationException
{
    public function decisionCode(): string
    {
        return 'invalid_signature';
    }
}
