<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Integrations\Contracts\AlertChannelProvider;
use App\Models\AlertDelivery;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

final class SendAlertDelivery implements ShouldQueue
{
    use Queueable;

    public int $tries = 4;

    /** @var list<int> */
    public array $backoff = [10, 60, 300, 900];

    public function __construct(public readonly AlertDelivery $delivery) {}

    public function handle(AlertChannelProvider $provider): void
    {
        if ($this->delivery->status === 'delivered') {
            return;
        }

        $this->delivery->forceFill(['attempts' => $this->delivery->attempts + 1, 'last_attempted_at' => now()])->save();
        $reference = $provider->send($this->delivery);
        $this->delivery->forceFill(['status' => 'sent', 'provider' => 'http_gateway', 'provider_reference' => $reference, 'sent_at' => now(), 'failure_reason' => null])->save();
    }

    public function failed(?Throwable $exception): void
    {
        $this->delivery->forceFill(['status' => 'failed', 'failure_reason' => $exception?->getMessage()])->save();
    }
}
