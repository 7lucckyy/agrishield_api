<?php

declare(strict_types=1);

namespace App\Integrations\Alerts;

use App\Integrations\Contracts\AlertChannelProvider;
use App\Models\AlertDelivery;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class HttpAlertChannelProvider implements AlertChannelProvider
{
    public function send(AlertDelivery $delivery): string
    {
        $url = (string) config('alerts.gateway.url');
        $token = (string) config('alerts.gateway.token');
        if ($url === '' || $token === '') {
            throw new RuntimeException('The multi-channel alert gateway is not configured.');
        }

        $delivery->loadMissing('advisory.farm');
        $response = $this->client($token)->post($url, [
            'channel' => $delivery->channel,
            'destination' => $delivery->destination,
            'language' => $delivery->user->alertPreference?->language ?? 'en',
            'message' => [
                'title' => $delivery->advisory->title,
                'body' => $delivery->advisory->summary,
                'severity' => $delivery->advisory->severity?->value,
                'farm' => $delivery->advisory->farm->name,
            ],
            'idempotency_key' => 'alert-delivery-'.$delivery->getKey(),
        ])->throw()->json();

        $reference = data_get($response, 'id') ?? data_get($response, 'reference');
        if (! is_string($reference) || $reference === '') {
            throw new RuntimeException('The alert gateway response did not include a delivery reference.');
        }

        return $reference;
    }

    private function client(string $token): PendingRequest
    {
        return Http::asJson()
            ->withToken($token)
            ->connectTimeout((int) config('alerts.gateway.connect_timeout', 5))
            ->timeout((int) config('alerts.gateway.read_timeout', 15))
            ->retry(2, 300, throw: false);
    }
}
