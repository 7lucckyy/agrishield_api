<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Contracts\Debug\ShouldntReport;
use RuntimeException;

final class LastOrganizationAdminRequiredException extends RuntimeException implements ShouldntReport
{
    public function __construct()
    {
        parent::__construct('The organization must retain at least one active administrator.');
    }
}
