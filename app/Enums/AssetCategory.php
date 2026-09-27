<?php

declare(strict_types=1);

namespace App\Enums;

enum AssetCategory: string
{
    case SolarIrrigation = 'solar_irrigation';
    case DripIrrigation = 'drip_irrigation';
    case WaterStorage = 'water_storage';
    case HermeticStorage = 'hermetic_storage';
    case Mechanization = 'mechanization';
    case ProcessingEquipment = 'processing_equipment';
}
