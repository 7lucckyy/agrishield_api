<?php

declare(strict_types=1);

namespace App\Enums;

enum ProviderStatus: string
{
    case Pending = 'pending';
    case Registered = 'registered';
    case Failed = 'failed';
    case Unsupported = 'unsupported';
}
