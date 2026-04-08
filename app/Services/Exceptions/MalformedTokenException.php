<?php

namespace App\Services\Exceptions;

class MalformedTokenException extends TokenVerificationException
{
    public function decisionCode(): string
    {
        return 'invalid_signature';
    }
}
