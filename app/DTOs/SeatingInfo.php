<?php

namespace App\DTOs;

readonly class SeatingInfo
{
    public function __construct(
        public string $seat,
        public ?string $area = null,
        public ?string $directions = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            seat: (string) $data['seat'],
            area: isset($data['area']) && is_string($data['area']) ? $data['area'] : null,
            directions: isset($data['directions']) && is_string($data['directions']) ? $data['directions'] : null,
        );
    }

    public function toArray(): array
    {
        return [
            'seat' => $this->seat,
            'area' => $this->area,
            'directions' => $this->directions,
        ];
    }
}
