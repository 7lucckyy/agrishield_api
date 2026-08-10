<?php

declare(strict_types=1);

namespace App\Enums;

enum CropCycleStatus: string
{
    case Planned = 'planned';
    case Active = 'active';
    case Harvested = 'harvested';
    case Abandoned = 'abandoned';

    public function canTransitionTo(self $next): bool
    {
        return match ($this) {
            self::Planned => in_array($next, [self::Active, self::Abandoned], true),
            self::Active => in_array($next, [self::Harvested, self::Abandoned], true),
            self::Harvested, self::Abandoned => false,
        };
    }
}
