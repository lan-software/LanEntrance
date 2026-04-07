<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class LanCoreClient
{
    public function ssoAuthorizeUrl(): string
    {
        $this->ensureEnabled();

        return rtrim((string) config('lancore.base_url'), '/')
            .'/sso/authorize?'
            .http_build_query([
                'app' => config('lancore.app_slug'),
                'redirect_uri' => config('lancore.callback_url'),
            ]);
    }

    /**
     * @return array{id:int, username:string, email:?string, roles:array<int,string>}
     */
    public function exchangeCode(string $code): array
    {
        $this->ensureEnabled();

        try {
            $response = $this->http()->post('/api/integration/sso/exchange', [
                'code' => $code,
            ]);
        } catch (ConnectionException $e) {
            throw new RuntimeException('LanCore is unreachable.', 0, $e);
        }

        if (! $response->successful()) {
            throw new RuntimeException((string) ($response->json('error') ?? 'SSO exchange failed.'), $response->status());
        }

        $data = $response->json('data');

        if (! is_array($data) || ! isset($data['id'], $data['username'])) {
            throw new RuntimeException('Invalid LanCore user payload.');
        }

        return [
            'id' => (int) $data['id'],
            'username' => (string) $data['username'],
            'email' => isset($data['email']) && is_string($data['email']) ? $data['email'] : null,
            'roles' => array_values(array_filter($data['roles'] ?? [], 'is_string')),
        ];
    }

    // ── Entrance API methods ─────────────────────────────────────────

    /**
     * @param  array<string, mixed>  $metadata  Audit metadata (operator_id, timestamp, etc.)
     */
    public function validateTicket(string $token, array $metadata): array
    {
        $this->ensureEnabled();

        return $this->http()->post('/api/entrance/validate', [
            'token' => $token,
            ...$metadata,
        ])->throw()->json();
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function confirmCheckin(string $token, string $validationId, array $metadata): array
    {
        $this->ensureEnabled();

        return $this->http()->post('/api/entrance/checkin', [
            'token' => $token,
            'validation_id' => $validationId,
            ...$metadata,
        ])->throw()->json();
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function confirmVerifyCheckin(string $token, string $validationId, array $metadata): array
    {
        $this->ensureEnabled();

        return $this->http()->post('/api/entrance/verify-checkin', [
            'token' => $token,
            'validation_id' => $validationId,
            ...$metadata,
        ])->throw()->json();
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function confirmPayment(string $token, string $validationId, string $paymentMethod, string $amount, array $metadata): array
    {
        $this->ensureEnabled();

        return $this->http()->post('/api/entrance/confirm-payment', [
            'token' => $token,
            'validation_id' => $validationId,
            'payment_method' => $paymentMethod,
            'amount' => $amount,
            ...$metadata,
        ])->throw()->json();
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function submitOverride(string $token, string $validationId, string $reason, array $metadata): array
    {
        $this->ensureEnabled();

        return $this->http()->post('/api/entrance/override', [
            'token' => $token,
            'validation_id' => $validationId,
            'reason' => $reason,
            ...$metadata,
        ])->throw()->json();
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function searchAttendees(string $query, array $metadata): array
    {
        $this->ensureEnabled();

        return $this->http()->get('/api/entrance/search', [
            'q' => $query,
            ...$metadata,
        ])->throw()->json();
    }

    /**
     * Fetch entrance analytics/stats from LanCore.
     *
     * @return array<string, mixed>
     */
    public function getEntranceStats(?int $eventId = null): array
    {
        $this->ensureEnabled();

        return $this->http()->get('/api/entrance/stats', array_filter([
            'event_id' => $eventId,
        ]))->throw()->json();
    }

    /**
     * Fetch available events from LanCore.
     *
     * @return array<int, array{id: int, name: string, start_date: string|null, end_date: string|null}>
     */
    public function getEvents(): array
    {
        $this->ensureEnabled();

        return $this->http()->get('/api/entrance/events')->throw()->json('events', []);
    }

    // ── Internal ────────────────────────────────────────────────────

    private function ensureEnabled(): void
    {
        if (! config('lancore.enabled')) {
            throw new RuntimeException('LanCore integration is disabled.');
        }
    }

    private function http()
    {
        return Http::baseUrl((string) (config('lancore.internal_url') ?? config('lancore.base_url')))
            ->timeout((int) config('lancore.timeout', 5))
            ->retry((int) config('lancore.retries', 2), (int) config('lancore.retry_delay', 100))
            ->withToken((string) config('lancore.token'));
    }
}
