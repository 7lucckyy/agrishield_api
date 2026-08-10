<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Contracts\Debug\ShouldntReport;
use RuntimeException;

final class ActiveCropCycleExistsException extends RuntimeException implements ShouldntReport
{
    public function __construct()
    {
        parent::__construct('This farm already has an active crop cycle.');
    }
}
