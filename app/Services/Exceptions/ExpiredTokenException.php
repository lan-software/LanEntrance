<?php

namespace App\Services\Exceptions;

class ExpiredTokenException extends TokenVerificationException
{
    public function decisionCode(): string
    {
        return 'expired';
    }
}
