<?php

declare(strict_types=1);

namespace App\Enums;

enum FinancePartnerType: string
{
    case NonInterestBank = 'non_interest_bank';
    case DevelopmentFinanceInstitution = 'development_finance_institution';
    case DepositMoneyBank = 'deposit_money_bank';
}
