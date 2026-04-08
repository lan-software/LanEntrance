<?php

namespace App\Services\Exceptions;

use RuntimeException;

abstract class TokenVerificationException extends RuntimeException
{
    abstract public function decisionCode(): string;
}
