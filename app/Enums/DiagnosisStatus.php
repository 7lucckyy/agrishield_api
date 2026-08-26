<?php

declare(strict_types=1);

namespace App\Enums;

enum DiagnosisStatus: string
{
    case Queued = 'queued';
    case Submitted = 'submitted';
    case Processing = 'processing';
    case Completed = 'completed';
    case Failed = 'failed';
    case Expired = 'expired';

    /** @return list<self> */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Queued => [self::Submitted, self::Failed],
            self::Submitted => [self::Processing, self::Completed, self::Failed, self::Expired],
            self::Processing => [self::Completed, self::Failed, self::Expired],
            self::Completed, self::Failed, self::Expired => [],
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
