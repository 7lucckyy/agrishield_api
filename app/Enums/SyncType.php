<?php

declare(strict_types=1);

namespace App\Enums;

enum SyncType: string
{
    case FarmRegistration = 'farm_registration';
    case SoilHealth = 'soil_health';
    case Weather = 'weather';
    case CropHealth = 'crop_health';
    case WaterStress = 'water_stress';
    case SoilMoisture = 'soil_moisture';
    case IrrigationAdvisory = 'irrigation_advisory';
    case PestForewarning = 'pest_forewarning';
    case CropPractices = 'crop_practices';
    case DiagnosisSubmit = 'diagnosis_submit';
    case DiagnosisPoll = 'diagnosis_poll';

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }
}
