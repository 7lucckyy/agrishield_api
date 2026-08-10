<?php

declare(strict_types=1);

namespace App\Enums;

enum AdvisoryType: string
{
    case PestWarning = 'pest_warning';
    case DiseaseWarning = 'disease_warning';
    case Irrigation = 'irrigation';
    case CropPractice = 'crop_practice';
    case WeatherAction = 'weather_action';
    case Nutrient = 'nutrient';
    case CropHealth = 'crop_health';
}
