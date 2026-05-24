<?php

namespace App\Services;

readonly class VerifiedToken
{
    public function __construct(
        public string $tid,
        public string $nonce,
        public int $iat,
        public int $exp,
        public string $evt,
        public string $kid,
    ) {}
}
