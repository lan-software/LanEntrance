<?php

namespace App\Services\Exceptions;

class UnknownKidException extends TokenVerificationException
{
    public function decisionCode(): string
    {
        return 'unknown_kid';
    }
}
