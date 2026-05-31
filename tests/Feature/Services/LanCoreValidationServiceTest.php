<?php

use App\Models\User;
use App\Services\LanCoreValidationService;
use App\Services\TicketSignatureVerifier;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config()->set('lancore.enabled', true);
    config()->set('lancore.base_url', 'http://lancore.test');
    config()->set('lancore.internal_url', 'http://lancore.test');
    config()->set('lancore.token', 'lci_integration_token');
    config()->set('lancore.entrance.signing_keys_cache_store', 'array');
    config()->set('lancore.token_format.version', 'LCT1');

    Cache::store('array')->flush();

    $kp = sodium_crypto_sign_keypair();
    $this->publicKey = sodium_crypto_sign_publickey($kp);
    $this->secretKey = sodium_crypto_sign_secretkey($kp);
    $this->kid = 'svc-key';

    // Publish the signing key so validate() pre-checks pass for happy-path tokens.
    Cache::store('array')->put('lancore:signing-key:'.$this->kid, $this->publicKey, 3600);

    $this->service = app(LanCoreValidationService::class);
    $this->operator = User::factory()->lanCoreUser('01J0000000OPERATOR00000001')->create();
});

/** Build an LCT1 token signed with the given key, allowing claim overrides. */
function svcToken(string $secretKey, string $kid, array $claims = []): string
{
    $body = TicketSignatureVerifier::base64UrlEncode(json_encode([
        'tid' => '01HZ0CNTRACTTCKET000000001',
        'nonce' => 'n',
        'iat' => time() - 1,
        'exp' => time() + 3600,
        'evt' => '01HZ0CNTRACTEVENT000000004',
        ...$claims,
    ]));
    $signingInput = 'LCT1.'.$kid.'.'.$body;
    $sig = sodium_crypto_sign_detached($signingInput, $secretKey);

    return $signingInput.'.'.TicketSignatureVerifier::base64UrlEncode($sig);
}

it('delegates checkin to the confirm-checkin endpoint', function () {
    Http::fake(['*/api/entrance/checkin' => Http::response(lancoreFixture('checkin-success'))]);

    $result = $this->service->checkin('any-token', 'val_123', $this->operator);

    expect($result['decision'])->toBe('valid');
    Http::assertSent(fn ($req) => str_contains($req->url(), '/api/entrance/checkin')
        && $req['validation_id'] === 'val_123');
});

it('delegates verifyCheckin to the verify-checkin endpoint', function () {
    Http::fake(['*/api/entrance/verify-checkin' => Http::response(lancoreFixture('verify-checkin-success'))]);

    $result = $this->service->verifyCheckin('any-token', 'val_123', $this->operator);

    expect($result)->toBeArray();
    Http::assertSent(fn ($req) => str_contains($req->url(), '/api/entrance/verify-checkin'));
});

it('delegates confirmPayment to the confirm-payment endpoint with payment fields', function () {
    Http::fake(['*/api/entrance/confirm-payment' => Http::response(lancoreFixture('confirm-payment-success'))]);

    $result = $this->service->confirmPayment('any-token', 'val_123', 'cash', '42.00', $this->operator);

    expect($result)->toBeArray();
    Http::assertSent(fn ($req) => str_contains($req->url(), '/api/entrance/confirm-payment')
        && $req['payment_method'] === 'cash'
        && $req['amount'] === '42.00');
});

it('delegates override to the override endpoint with the reason', function () {
    Http::fake(['*/api/entrance/override' => Http::response(lancoreFixture('override-success'))]);

    $result = $this->service->override('any-token', 'val_123', 'group leader present', $this->operator);

    expect($result)->toBeArray();
    Http::assertSent(fn ($req) => str_contains($req->url(), '/api/entrance/override')
        && $req['reason'] === 'group leader present');
});

it('returns search results from the search endpoint', function () {
    Http::fake(['*/api/entrance/search*' => Http::response(lancoreFixture('lookup-results'))]);

    $results = $this->service->search('mustermann', $this->operator);

    expect($results)->toHaveCount(2)
        ->and($results[0]['name'])->toBe('Max Mustermann');
});

it('returns an empty array when search hits an unavailable LanCore', function () {
    Http::fake(['*/api/entrance/search*' => fn () => throw new ConnectionException('Connection refused')]);

    expect($this->service->search('mustermann', $this->operator))->toBe([]);
});

it('returns an empty array when search yields a client error', function () {
    Http::fake(['*/api/entrance/search*' => Http::response(['error' => 'nope'], 422)]);

    expect($this->service->search('mustermann', $this->operator))->toBe([]);
});

it('maps a 404 from LanCore to an invalid decision', function () {
    Http::fake(['*/api/entrance/checkin' => Http::response(['error' => 'Ticket not found.'], 404)]);

    $result = $this->service->checkin('any-token', 'val_123', $this->operator);

    expect($result['decision'])->toBe('invalid')
        ->and($result['message'])->toBe('Ticket not found.')
        ->and($result['degraded'])->toBeFalse();
});

it('maps a 422 from LanCore to a validation_error response', function () {
    Http::fake(['*/api/entrance/checkin' => Http::response(['error' => 'Invalid validation id.'], 422)]);

    $result = $this->service->checkin('any-token', 'val_123', $this->operator);

    expect($result['error'])->toBe('validation_error')
        ->and($result['message'])->toBe('Invalid validation id.');
});

it('maps a 429 from LanCore to a rate_limited response', function () {
    Http::fake(['*/api/entrance/checkin' => Http::response([], 429)]);

    $result = $this->service->checkin('any-token', 'val_123', $this->operator);

    expect($result['error'])->toBe('rate_limited')
        ->and($result['message'])->toBe('Too many requests. Please wait a moment.');
});

it('maps an unmapped client error to a degraded response carrying the message', function () {
    Http::fake(['*/api/entrance/checkin' => Http::response(['error' => 'Bad request.'], 400)]);

    $result = $this->service->checkin('any-token', 'val_123', $this->operator);

    expect($result['decision'])->toBe('error')
        ->and($result['degraded'])->toBeTrue()
        ->and($result['message'])->toBe('Bad request.');
});

it('returns a degraded response when LanCore is unreachable', function () {
    Http::fake(['*/api/entrance/checkin' => fn () => throw new ConnectionException('timeout')]);

    $result = $this->service->checkin('any-token', 'val_123', $this->operator);

    expect($result['decision'])->toBe('error')
        ->and($result['degraded'])->toBeTrue()
        ->and($result['message'])->toBe('LanCore is currently unreachable. Please try again.');
});

it('returns a degraded response on a LanCore server error', function () {
    Http::fake(['*/api/entrance/checkin' => Http::response(['error' => 'boom'], 500)]);

    $result = $this->service->checkin('any-token', 'val_123', $this->operator);

    expect($result['degraded'])->toBeTrue()
        ->and($result['decision'])->toBe('error');
});

it('attaches operator audit metadata to outgoing requests', function () {
    Http::fake(['*/api/entrance/checkin' => Http::response(lancoreFixture('checkin-success'))]);

    session()->put('entrance_event_id', '01HZ0CNTRACTEVENT000000004');

    $this->service->checkin('any-token', 'val_123', $this->operator);

    Http::assertSent(fn ($req) => $req['operator_id'] === '01J0000000OPERATOR00000001'
        && $req['event_id'] === '01HZ0CNTRACTEVENT000000004'
        && ! empty($req['timestamp']));
});

it('rejects a token signed with an unknown key id during validate', function () {
    // No signing-keys endpoint, no cached key for this kid -> UnknownKidException.
    Http::fake(['*/api/entrance/signing-keys' => Http::response(['keys' => []])]);

    $token = svcToken($this->secretKey, 'mystery-kid');

    $result = $this->service->validate($token, $this->operator);

    expect($result['decision'])->toBe('unknown_kid')
        ->and($result['message'])->toBe('Ticket was signed with an unknown key.')
        ->and($result['override_allowed'])->toBeFalse();
    Http::assertNotSent(fn ($req) => str_contains($req->url(), '/api/entrance/validate'));
});

it('rejects an expired token during validate without calling LanCore', function () {
    Http::fake(['*/api/entrance/validate' => Http::response(lancoreFixture('validate-valid'))]);

    $token = svcToken($this->secretKey, $this->kid, ['exp' => time() - 10]);

    $result = $this->service->validate($token, $this->operator);

    expect($result['decision'])->toBe('expired')
        ->and($result['message'])->toBe('Ticket has expired.');
    Http::assertNotSent(fn ($req) => str_contains($req->url(), '/api/entrance/validate'));
});

it('delegates a valid token to the LanCore validate endpoint', function () {
    Http::fake(['*/api/entrance/validate' => Http::response(lancoreFixture('validate-valid'))]);

    $token = svcToken($this->secretKey, $this->kid);

    $result = $this->service->validate($token, $this->operator);

    expect($result['decision'])->toBe('valid');
    Http::assertSent(fn ($req) => str_contains($req->url(), '/api/entrance/validate'));
});
