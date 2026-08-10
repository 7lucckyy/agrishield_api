<?php

declare(strict_types=1);

namespace App\Enums;

enum SyncTrigger: string
{
    case Schedule = 'schedule';
    case Manual = 'manual';
    case Event = 'event';
    case Retry = 'retry';
    case Backfill = 'backfill';

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }
}
