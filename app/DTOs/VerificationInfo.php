<?php

namespace App\DTOs;

readonly class VerificationInfo
{
    /**
     * @param  array<int, VerificationCheck>  $checks
     */
    public function __construct(
        public string $message,
        public array $checks,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            message: (string) $data['message'],
            checks: array_map(
                fn (array $check) => VerificationCheck::fromArray($check),
                $data['checks'] ?? [],
            ),
        );
    }

    public function toArray(): array
    {
        return [
            'message' => $this->message,
            'checks' => array_map(fn (VerificationCheck $check) => $check->toArray(), $this->checks),
        ];
    }
}
