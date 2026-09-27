<?php

declare(strict_types=1);

namespace App\Enums;

enum AssetFinanceStatus: string
{
    case Submitted = 'submitted';
    case UnderReview = 'under_review';
    case Approved = 'approved';
    case Declined = 'declined';
    case AssetOrdered = 'asset_ordered';
    case Delivered = 'delivered';
    case Active = 'active';
    case Repaid = 'repaid';
    case Defaulted = 'defaulted';
    case Withdrawn = 'withdrawn';

    /** @return list<self> */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Submitted => [self::UnderReview, self::Withdrawn],
            self::UnderReview => [self::Approved, self::Declined, self::Withdrawn],
            self::Approved => [self::AssetOrdered, self::Withdrawn],
            self::AssetOrdered => [self::Delivered, self::Withdrawn],
            self::Delivered => [self::Active],
            self::Active => [self::Repaid, self::Defaulted],
            self::Declined, self::Repaid, self::Defaulted, self::Withdrawn => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(static fn (self $status): string => $status->value, self::cases());
    }
}
