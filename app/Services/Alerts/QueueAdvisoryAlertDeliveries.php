<?php

declare(strict_types=1);

namespace App\Services\Alerts;

use App\Jobs\SendAlertDelivery;
use App\Models\Advisory;
use App\Models\FarmerAlertPreference;
use App\Models\User;

final class QueueAdvisoryAlertDeliveries
{
    public function execute(Advisory $advisory): void
    {
        $advisory->loadMissing('farm.owner.alertPreference');
        $user = $advisory->farm->owner;
        $preference = $user->alertPreference ?? $this->defaultPreference($user);
        if (! $this->meetsThreshold($advisory, $preference)) {
            return;
        }

        foreach ($this->channels($preference) as $channel => $destination) {
            $delivery = $advisory->alertDeliveries()->firstOrCreate(
                ['user_id' => $user->getKey(), 'channel' => $channel],
                ['destination' => $destination, 'status' => $channel === 'in_app' ? 'delivered' : 'pending', 'delivered_at' => $channel === 'in_app' ? now() : null],
            );

            if ($delivery->wasRecentlyCreated && $channel !== 'in_app') {
                SendAlertDelivery::dispatch($delivery)->afterCommit();
            }
        }
    }

    private function defaultPreference(User $user): FarmerAlertPreference
    {
        return $user->alertPreference()->create([
            'phone' => $user->phone,
            'language' => $user->locale,
            'in_app_enabled' => true,
            'push_enabled' => false,
            'sms_enabled' => false,
            'whatsapp_enabled' => false,
            'voice_call_enabled' => false,
            'consent' => false,
        ]);
    }

    /** @return array<string, string|null> */
    private function channels(FarmerAlertPreference $preference): array
    {
        $channels = [];
        if ($preference->in_app_enabled) {
            $channels['in_app'] = null;
        }
        if (! $preference->consent) {
            return $channels;
        }
        if ($preference->sms_enabled && $preference->phone !== null) {
            $channels['sms'] = $preference->phone;
        }
        if ($preference->whatsapp_enabled && $preference->whatsapp_phone !== null) {
            $channels['whatsapp'] = $preference->whatsapp_phone;
        }
        if ($preference->voice_call_enabled && $preference->phone !== null) {
            $channels['voice_call'] = $preference->phone;
        }

        return $channels;
    }

    private function meetsThreshold(Advisory $advisory, FarmerAlertPreference $preference): bool
    {
        $rank = ['info' => 0, 'low' => 1, 'medium' => 2, 'high' => 3, 'critical' => 4];

        return $rank[$advisory->severity->value] >= ($rank[$preference->minimum_severity] ?? 2);
    }
}
