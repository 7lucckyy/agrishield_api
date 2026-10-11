<?php

declare(strict_types=1);

namespace App\Enums;

enum AgriculturalPartnerType: string
{
    case Buyer = 'buyer';
    case Aggregator = 'aggregator';
    case InputSupplier = 'input_supplier';
    case MachineryProvider = 'machinery_provider';
    case FinancialInstitution = 'financial_institution';
    case Insurer = 'insurer';
    case LogisticsProvider = 'logistics_provider';
    case StorageProvider = 'storage_provider';
}
