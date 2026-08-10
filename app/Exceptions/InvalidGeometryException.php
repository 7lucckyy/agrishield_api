<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Enums\GeometryValidationError;
use RuntimeException;

final class InvalidGeometryException extends RuntimeException
{
    public function __construct(public readonly GeometryValidationError $error)
    {
        parent::__construct($error->message());
    }
}
