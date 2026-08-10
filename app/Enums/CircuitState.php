<?php

declare(strict_types=1);

namespace App\Enums;

enum CircuitState: string
{
    case Closed = 'closed';
    case Open = 'open';
    case HalfOpen = 'half_open';

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }
}
