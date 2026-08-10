<?php

declare(strict_types=1);

use App\Exceptions\Provider\ProviderAuthFailed;
use App\Exceptions\Provider\ProviderContractViolation;
use App\Exceptions\Provider\ProviderException;
use App\Exceptions\Provider\ProviderRateLimited;
use App\Exceptions\Provider\ProviderRejected;
use App\Exceptions\Provider\ProviderTimeout;
use App\Exceptions\Provider\ProviderUnavailable;
use App\Integrations\Support\ProviderExceptionMapper;
use GuzzleHttp\Psr7\Response as PsrResponse;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;

test('provider http statuses map to the stable exception taxonomy', function (
    int $status,
    string $exceptionClass,
    string $errorCode,
    bool $retryable,
) {
    $exception = (new ProviderExceptionMapper)->fromResponse(
        new Response(new PsrResponse($status)),
        'provider-request-123',
    );

    expect($exception)->toBeInstanceOf($exceptionClass)
        ->and($exception->errorCode)->toBe($errorCode)
        ->and($exception->providerRequestId)->toBe('provider-request-123')
        ->and($exception->retryable)->toBe($retryable);
})->with([
    'unauthorized' => [401, ProviderAuthFailed::class, 'provider_auth_failed', false],
    'forbidden' => [403, ProviderAuthFailed::class, 'provider_auth_failed', false],
    'request timeout' => [408, ProviderTimeout::class, 'provider_timeout', true],
    'rate limited' => [429, ProviderRateLimited::class, 'provider_rate_limited', true],
    'server error' => [503, ProviderUnavailable::class, 'provider_unavailable', true],
    'rejected request' => [422, ProviderRejected::class, 'provider_rejected', false],
    'unexpected success response' => [200, ProviderContractViolation::class, 'provider_contract_violation', false],
]);

test('connection failures map to retryable provider unavailability', function () {
    $previous = new ConnectionException('Connection refused');
    $exception = (new ProviderExceptionMapper)->fromThrowable($previous, 'request-456');

    expect($exception)->toBeInstanceOf(ProviderUnavailable::class)
        ->and($exception->errorCode)->toBe('provider_unavailable')
        ->and($exception->providerRequestId)->toBe('request-456')
        ->and($exception->retryable)->toBeTrue()
        ->and($exception->getPrevious())->toBe($previous);
});

test('an already mapped provider exception is preserved', function () {
    $mapped = new ProviderRejected('Rejected', 'provider_rejected');

    expect((new ProviderExceptionMapper)->fromThrowable($mapped))->toBe($mapped);
});

test('unknown adapter failures become non retryable contract violations', function () {
    $exception = (new ProviderExceptionMapper)->fromThrowable(new RuntimeException('Invalid shape'));

    expect($exception)->toBeInstanceOf(ProviderContractViolation::class)
        ->and($exception)->toBeInstanceOf(ProviderException::class)
        ->and($exception->retryable)->toBeFalse();
});
