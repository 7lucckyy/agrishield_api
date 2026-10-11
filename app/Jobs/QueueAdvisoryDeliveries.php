<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Advisory;
use App\Services\Alerts\QueueAdvisoryAlertDeliveries;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class QueueAdvisoryDeliveries implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [5, 30, 120];

    public function __construct(public readonly Advisory $advisory) {}

    public function handle(QueueAdvisoryAlertDeliveries $queue): void
    {
        $queue->execute($this->advisory);
    }
}
