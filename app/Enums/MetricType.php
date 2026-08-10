<?php

declare(strict_types=1);

namespace App\Enums;

enum MetricType: string
{
    case Ndvi = 'ndvi';
    case Lswi = 'lswi';
    case SoilMoisture = 'soil_moisture';
    case SoilNitrogen = 'soil_nitrogen';
    case SoilPhosphorus = 'soil_phosphorus';
    case SoilPotassium = 'soil_potassium';
    case SoilOrganicCarbon = 'soil_organic_carbon';
    case SoilPh = 'soil_ph';
}
