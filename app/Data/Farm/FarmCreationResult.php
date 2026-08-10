<?php

declare(strict_types=1);

namespace App\Data\Farm;

use App\Models\Farm;
use App\Models\SyncRun;

final readonly class FarmCreationResult
{
    public function __construct(
        public Farm $farm,
        public SyncRun $registrationRun,
    ) {}
}
