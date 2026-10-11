<?php

declare(strict_types=1);

namespace App\Enums;

enum AgriculturalServiceRequestStatus: string
{
    case Submitted = 'submitted';
    case Reviewing = 'reviewing';
    case Approved = 'approved';
    case Declined = 'declined';
    case Fulfilled = 'fulfilled';
    case Cancelled = 'cancelled';

    /** @return list<self> */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Submitted => [self::Reviewing, self::Cancelled],
            self::Reviewing => [self::Approved, self::Declined, self::Cancelled],
            self::Approved => [self::Fulfilled, self::Cancelled],
            self::Declined, self::Fulfilled, self::Cancelled => [],
        };
    }
}
