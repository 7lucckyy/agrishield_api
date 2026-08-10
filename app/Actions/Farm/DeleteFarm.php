<?php

declare(strict_types=1);

namespace App\Actions\Farm;

use App\Models\Farm;

final class DeleteFarm
{
    public function execute(Farm $farm): void
    {
        $farm->delete();
    }
}
