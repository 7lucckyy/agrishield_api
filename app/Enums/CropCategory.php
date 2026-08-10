<?php

declare(strict_types=1);

namespace App\Enums;

enum CropCategory: string
{
    case Cereal = 'cereal';
    case Pulse = 'pulse';
    case Oilseed = 'oilseed';
    case Vegetable = 'vegetable';
    case Fruit = 'fruit';
    case Fibre = 'fibre';
    case Other = 'other';
}
