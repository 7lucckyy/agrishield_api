<?php

declare(strict_types=1);

namespace App\Enums;

enum IrrigationContext: string
{
    case RainFed = 'rain_fed';
    case Irrigated = 'irrigated';
    case Mixed = 'mixed';
}
