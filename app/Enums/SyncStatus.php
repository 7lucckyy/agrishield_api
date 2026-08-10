<?php

declare(strict_types=1);

namespace App\Enums;

enum SyncStatus: string
{
    case Pending = 'pending';
    case Running = 'running';
    case Succeeded = 'succeeded';
    case Partial = 'partial';
    case Failed = 'failed';
    case Skipped = 'skipped';

    /** @return list<self> */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Pending => [self::Running, self::Skipped],
            self::Running => [self::Succeeded, self::Partial, self::Failed],
            self::Partial, self::Failed => [self::Pending],
            self::Succeeded, self::Skipped => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }
}
