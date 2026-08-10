<?php

declare(strict_types=1);

namespace App\Enums;

enum IntegrationStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Error = 'error';
}
