<?php

namespace App\DTOs;

readonly class VerificationCheck
{
    public function __construct(
        public string $label,
        public ?string $instruction = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            label: (string) $data['label'],
            instruction: isset($data['instruction']) && is_string($data['instruction']) ? $data['instruction'] : null,
        );
    }

    public function toArray(): array
    {
        return [
            'label' => $this->label,
            'instruction' => $this->instruction,
        ];
    }
}
