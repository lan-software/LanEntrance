<?php

namespace App\DTOs;

readonly class PaymentItem
{
    public function __construct(
        public string $name,
        public string $price,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: (string) $data['name'],
            price: (string) $data['price'],
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'price' => $this->price,
        ];
    }
}
