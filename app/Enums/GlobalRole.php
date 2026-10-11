<?php

namespace App\Enums;

enum GlobalRole: string
{
    case PlatformAdmin = 'platform_admin';
    case Agronomist = 'agronomist';
    case EquipmentManager = 'equipment_manager';
}
