<?php

declare(strict_types=1);

namespace App\Enums;

enum FarmProviderLinkStatus: string
{
    case Pending = 'pending';
    case Registered = 'registered';
    case Failed = 'failed';
    case Stale = 'stale';
}
