<?php

namespace App\Services;

use App\DTOs\ValidationResponse;
use App\Models\User;
use App\Services\Exceptions\TokenVerificationException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;

class LanCoreValidationService
{
    public function __construct(
        private readonly LanCoreClient $client,
        private readonly TicketSignatureVerifier $verifier,
    ) {}

    public function validate(string $token, User $operator): array
    {
        try {
            $this->verifier->verify($token);
        } catch (TokenVerificationException $e) {
            return $this->precheckRejection($e);
        }

        return $this->execute(
            fn () => $this->client->validateTicket($token, $this->buildMetadata($operator)),
        );
    }

    public function checkin(string $token, string $validationId, User $operator): array
    {
        return $this->execute(
            fn () => $this->client->confirmCheckin($token, $validationId, $this->buildMetadata($operator)),
        );
    }

    public function verifyCheckin(string $token, string $validationId, User $operator): array
    {
        return $this->execute(
            fn () => $this->client->confirmVerifyCheckin($token, $validationId, $this->buildMetadata($operator)),
        );
    }

    public function confirmPayment(string $token, string $validationId, string $paymentMethod, string $amount, User $operator): array
    {
        return $this->execute(
            fn () => $this->client->confirmPayment($token, $validationId, $paymentMethod, $amount, $this->buildMetadata($operator)),
        );
    }

    public function override(string $token, string $validationId, string $reason, User $operator): array
    {
        return $this->execute(
            fn () => $this->client->submitOverride($token, $validationId, $reason, $this->buildMetadata($operator)),
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function search(string $query, User $operator): array
    {
        try {
            $response = $this->client->searchAttendees($query, $this->buildMetadata($operator));

            return $response['results'] ?? [];
        } catch (ConnectionException) {
            return [];
        } catch (RequestException) {
            return [];
        }
    }

    /**
     * Execute a LanCore API call with standardized error handling.
     *
     * @param  callable(): array  $call
     */
    private function execute(callable $call): array
    {
        try {
            $data = $call();

            return ValidationResponse::fromLanCore($data)->toArray();
        } catch (ConnectionException) {
            return $this->degradedResponse('LanCore is currently unreachable. Please try again.');
        } catch (RequestException $e) {
            return $this->mapErrorResponse($e);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function buildMetadata(User $operator): array
    {
        return array_filter([
            'operator_id' => $operator->lancore_user_id,
            'operator_session' => session()->getId(),
            'timestamp' => now()->toISOString(),
            'client_info' => request()->userAgent(),
            'event_id' => session('entrance_event_id'),
        ], fn ($value) => $value !== null);
    }

    /**
     * @return array<string, mixed>
     */
    /**
     * @return array<string, mixed>
     */
    private function precheckRejection(TokenVerificationException $e): array
    {
        $code = $e->decisionCode();

        $messages = [
            'invalid_signature' => 'Ticket signature is invalid.',
            'unknown_kid' => 'Ticket was signed with an unknown key.',
            'expired' => 'Ticket has expired.',
        ];

        return [
            'decision' => $code,
            'message' => $messages[$code] ?? 'Ticket rejected.',
            'validation_id' => '',
            'degraded' => false,
            'override_allowed' => false,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function degradedResponse(string $message): array
    {
        return [
            'decision' => 'error',
            'message' => $message,
            'degraded' => true,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mapErrorResponse(RequestException $e): array
    {
        $status = $e->response->status();
        $body = $e->response->json() ?? [];

        return match (true) {
            $status === 404 => [
                'decision' => 'invalid',
                'message' => (string) ($body['message'] ?? 'Ticket not found.'),
                'degraded' => false,
            ],
            $status === 422 => [
                'error' => 'validation_error',
                'message' => (string) ($body['message'] ?? 'Invalid request.'),
                'degraded' => false,
                'details' => $body['details'] ?? $body['errors'] ?? [],
            ],
            $status === 429 => [
                'error' => 'rate_limited',
                'message' => 'Too many requests. Please wait a moment.',
                'degraded' => false,
            ],
            default => $this->degradedResponse(
                (string) ($body['message'] ?? 'An unexpected error occurred.'),
            ),
        };
    }
}
