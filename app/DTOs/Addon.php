<?php

namespace App\DTOs;

readonly class Addon
{
    public function __construct(
        public string $name,
        public ?string $info = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: (string) $data['name'],
            info: isset($data['info']) && is_string($data['info']) ? $data['info'] : null,
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'info' => $this->info,
        ];
    }
}
