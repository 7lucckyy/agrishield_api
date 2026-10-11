<?php

declare(strict_types=1);

namespace App\Enums;

enum AgriculturalOfferCategory: string
{
    case ProducePurchase = 'produce_purchase';
    case InputPurchase = 'input_purchase';
    case InputFinance = 'input_finance';
    case MachineryHire = 'machinery_hire';
    case Storage = 'storage';
    case Logistics = 'logistics';
    case CropInsurance = 'crop_insurance';
}
