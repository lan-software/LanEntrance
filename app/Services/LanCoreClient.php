<?php

namespace App\Services;

use App\Services\Exceptions\LanCoreUnavailableException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
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

    /**
     * Fetch the JWKS-style list of Ed25519 signing keys from LanCore.
     *
     * @return array<int, array{kid:string, kty:string, crv:string, x:string}>
     */
    public function fetchSigningKeys(): array
    {
        $this->ensureEnabled();

        $endpoint = (string) config('lancore.signing_keys_endpoint', 'api/entrance/signing-keys');

        try {
            $response = $this->http()->get($endpoint);
        } catch (ConnectionException $e) {
            throw new LanCoreUnavailableException('LanCore signing-keys endpoint is unreachable.', 0, $e);
        } catch (\Throwable $e) {
            if ($e instanceof RequestException && $e->response !== null) {
                $status = $e->response->status();

                if ($status >= 500) {
                    throw new LanCoreUnavailableException('LanCore signing-keys endpoint failed: '.$status, 0, $e);
                }

                throw new RuntimeException('LanCore signing-keys request failed: '.$status, $status, $e);
            }

            throw new LanCoreUnavailableException('LanCore signing-keys endpoint is unreachable.', 0, $e);
        }

        if (! $response->successful()) {
            if ($response->serverError()) {
                throw new LanCoreUnavailableException('LanCore signing-keys endpoint failed: '.$response->status());
            }

            throw new RuntimeException('LanCore signing-keys request failed: '.$response->status(), $response->status());
        }

        $keys = $response->json('keys');

        if (! is_array($keys)) {
            throw new RuntimeException('LanCore signing-keys response missing "keys" array.');
        }

        return array_values(array_filter(
            $keys,
            fn ($k) => is_array($k) && isset($k['kid'], $k['x']),
        ));
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
