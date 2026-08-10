<?php

declare(strict_types=1);

namespace App\Integrations\Support;

use App\Exceptions\Provider\ProviderAuthFailed;
use App\Exceptions\Provider\ProviderContractViolation;
use App\Exceptions\Provider\ProviderException;
use App\Exceptions\Provider\ProviderRateLimited;
use App\Exceptions\Provider\ProviderRejected;
use App\Exceptions\Provider\ProviderTimeout;
use App\Exceptions\Provider\ProviderUnavailable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Throwable;

final class ProviderExceptionMapper
{
    public function fromResponse(Response $response, ?string $providerRequestId = null): ProviderException
    {
        return match (true) {
            in_array($response->status(), [401, 403], true) => new ProviderAuthFailed(
                'The provider rejected its configured credentials.',
                'provider_auth_failed',
                $providerRequestId,
            ),
            $response->status() === 408 => new ProviderTimeout(
                'The provider request timed out.',
                'provider_timeout',
                $providerRequestId,
                true,
            ),
            $response->status() === 429 => new ProviderRateLimited(
                'The provider rate limit was reached.',
                'provider_rate_limited',
                $providerRequestId,
                true,
            ),
            $response->serverError() => new ProviderUnavailable(
                'The provider is temporarily unavailable.',
                'provider_unavailable',
                $providerRequestId,
                true,
            ),
            $response->clientError() => new ProviderRejected(
                'The provider rejected the request.',
                'provider_rejected',
                $providerRequestId,
            ),
            default => new ProviderContractViolation(
                'The provider returned an unexpected response.',
                'provider_contract_violation',
                $providerRequestId,
            ),
        };
    }

    public function fromThrowable(Throwable $exception, ?string $providerRequestId = null): ProviderException
    {
        if ($exception instanceof ProviderException) {
            return $exception;
        }

        if ($exception instanceof ConnectionException) {
            return new ProviderUnavailable(
                'The provider connection failed.',
                'provider_unavailable',
                $providerRequestId,
                true,
                $exception,
            );
        }

        return new ProviderContractViolation(
            'The provider adapter failed to map its response.',
            'provider_contract_violation',
            $providerRequestId,
            false,
            $exception,
        );
    }
}
