<?php

declare(strict_types=1);

namespace App\Enums;

enum FarmLedgerEntryType: string
{
    case Expense = 'expense';
    case Income = 'income';
    case Harvest = 'harvest';
}
