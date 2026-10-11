<?php

declare(strict_types=1);

namespace App\Enums;

enum EquipmentAcquisitionMode: string
{
    case Lease = 'lease';
    case FinancePurchase = 'finance_purchase';
}
