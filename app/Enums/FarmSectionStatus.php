<?php

declare(strict_types=1);

namespace App\Enums;

enum FarmSectionStatus: string
{
    case Active = 'active';
    case Fallow = 'fallow';
    case Inactive = 'inactive';
    case Archived = 'archived';
}
