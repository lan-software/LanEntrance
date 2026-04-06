<?php

namespace App\DTOs;

readonly class PaymentInfo
{
    /**
     * @param  array<int, PaymentItem>  $items
     * @param  array<int, string>  $methods
     */
    public function __construct(
        public string $amount,
        public string $currency,
        public array $items,
        public array $methods,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            amount: (string) $data['amount'],
            currency: (string) $data['currency'],
            items: array_map(
                fn (array $item) => PaymentItem::fromArray($item),
                $data['items'] ?? [],
            ),
            methods: array_values(array_filter($data['methods'] ?? [], 'is_string')),
        );
    }

    public function toArray(): array
    {
        return [
            'amount' => $this->amount,
            'currency' => $this->currency,
            'items' => array_map(fn (PaymentItem $item) => $item->toArray(), $this->items),
            'methods' => $this->methods,
        ];
    }
}
