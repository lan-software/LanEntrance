<?php

namespace App\DTOs;

readonly class ValidationResponse
{
    /**
     * @param  array{name: string, group?: string}|null  $attendee
     * @param  array<int, Addon>|null  $addons
     * @param  array{rule: string, message: string, members_checked_in: int, members_total: int}|null  $groupPolicy
     */
    public function __construct(
        public string $decision,
        public string $message,
        public string $validationId,
        public bool $degraded = false,
        public bool $overrideAllowed = false,
        public ?string $auditId = null,
        public ?array $attendee = null,
        public ?SeatingInfo $seating = null,
        public ?array $addons = null,
        public ?VerificationInfo $verification = null,
        public ?PaymentInfo $payment = null,
        public ?array $groupPolicy = null,
        public ?string $checkinId = null,
        public ?string $paymentId = null,
        public ?string $overrideId = null,
        public ?bool $receiptSent = null,
    ) {}

    public static function fromLanCore(array $data): self
    {
        return new self(
            decision: (string) ($data['decision'] ?? 'error'),
            message: (string) ($data['message'] ?? ''),
            validationId: (string) ($data['validation_id'] ?? $data['audit_id'] ?? ''),
            degraded: (bool) ($data['degraded'] ?? false),
            overrideAllowed: (bool) ($data['override_allowed'] ?? false),
            auditId: isset($data['audit_id']) ? (string) $data['audit_id'] : null,
            attendee: isset($data['attendee']) && is_array($data['attendee']) ? [
                'name' => (string) ($data['attendee']['name'] ?? ''),
                'group' => isset($data['attendee']['group']) ? (string) $data['attendee']['group'] : null,
            ] : null,
            seating: isset($data['seating']) && is_array($data['seating'])
                ? SeatingInfo::fromArray($data['seating'])
                : null,
            addons: isset($data['addons']) && is_array($data['addons'])
                ? array_map(fn (array $addon) => Addon::fromArray($addon), $data['addons'])
                : null,
            verification: isset($data['verification']) && is_array($data['verification'])
                ? VerificationInfo::fromArray($data['verification'])
                : null,
            payment: isset($data['payment']) && is_array($data['payment'])
                ? PaymentInfo::fromArray($data['payment'])
                : null,
            groupPolicy: isset($data['group_policy']) && is_array($data['group_policy'])
                ? $data['group_policy']
                : null,
            checkinId: isset($data['checkin_id']) ? (string) $data['checkin_id'] : null,
            paymentId: isset($data['payment_id']) ? (string) $data['payment_id'] : null,
            overrideId: isset($data['override_id']) ? (string) $data['override_id'] : null,
            receiptSent: isset($data['receipt_sent']) ? (bool) $data['receipt_sent'] : null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'decision' => $this->decision,
            'message' => $this->message,
            'validation_id' => $this->validationId,
            'degraded' => $this->degraded,
            'override_allowed' => $this->overrideAllowed,
            'audit_id' => $this->auditId,
            'attendee' => $this->attendee,
            'seating' => $this->seating?->toArray(),
            'addons' => $this->addons !== null
                ? array_map(fn (Addon $addon) => $addon->toArray(), $this->addons)
                : null,
            'verification' => $this->verification?->toArray(),
            'payment' => $this->payment?->toArray(),
            'group_policy' => $this->groupPolicy,
            'checkin_id' => $this->checkinId,
            'payment_id' => $this->paymentId,
            'override_id' => $this->overrideId,
            'receipt_sent' => $this->receiptSent,
        ], fn ($value) => $value !== null);
    }
}
