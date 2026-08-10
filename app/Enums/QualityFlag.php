<?php

declare(strict_types=1);

namespace App\Enums;

enum QualityFlag: string
{
    case Good = 'good';
    case PartialCloud = 'partial_cloud';
    case Cloudy = 'cloudy';
    case Unreliable = 'unreliable';
}
