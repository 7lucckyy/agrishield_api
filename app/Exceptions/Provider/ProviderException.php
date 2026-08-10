<?php

declare(strict_types=1);

namespace App\Exceptions\Provider;

use RuntimeException;
use Throwable;

abstract class ProviderException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly string $errorCode,
        public readonly ?string $providerRequestId = null,
        public readonly bool $retryable = false,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    /** @return array<string, string|bool|null> */
    public function context(): array
    {
        return [
            'error_code' => $this->errorCode,
            'provider_request_id' => $this->providerRequestId,
            'retryable' => $this->retryable,
        ];
    }
}
