<?php

namespace App\Services;

readonly class VerifiedToken
{
    public function __construct(
        public int $tid,
        public string $nonce,
        public int $iat,
        public int $exp,
        public int $evt,
        public string $kid,
    ) {}
}
