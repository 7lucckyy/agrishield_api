<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Contracts\Debug\ShouldntReport;
use RuntimeException;

final class InvalidTransitionException extends RuntimeException implements ShouldntReport
{
    public function __construct(string $message = 'The requested status transition is not allowed.')
    {
        parent::__construct($message);
    }
}
