<?php

declare(strict_types=1);

use App\Jobs\SendAlertDelivery;
use App\Models\Advisory;
use App\Models\Farm;
use App\Models\FarmerAlertPreference;
use App\Models\User;
use App\Services\Alerts\QueueAdvisoryAlertDeliveries;
use Illuminate\Support\Facades\Bus;

test('farmers control consent and destinations for multi-channel alerts', function () {
    $farmer = User::factory()->create(['phone' => '+2348012345678']);

    $this->actingAs($farmer)
        ->putJson('/api/v1/me/alert-preferences', [
            'in_app_enabled' => true,
            'push_enabled' => false,
            'sms_enabled' => true,
            'whatsapp_enabled' => true,
            'voice_call_enabled' => false,
            'phone' => '+2348012345678',
            'whatsapp_phone' => '+2348012345678',
            'language' => 'ha',
            'minimum_severity' => 'high',
            'consent' => true,
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.language', 'ha')
        ->assertJsonPath('data.sms_enabled', true);

    expect(FarmerAlertPreference::query()->whereBelongsTo($farmer)->whereNotNull('consented_at')->exists())->toBeTrue();
});

test('a high severity advisory creates auditable opted-in delivery records', function () {
    Bus::fake();
    $farmer = User::factory()->create();
    $farm = Farm::factory()->for($farmer, 'owner')->create();
    FarmerAlertPreference::factory()->for($farmer)->create(['whatsapp_enabled' => true, 'whatsapp_phone' => '+2348012345678', 'minimum_severity' => 'high']);
    $advisory = Advisory::factory()->for($farm)->create(['severity' => 'high']);

    app(QueueAdvisoryAlertDeliveries::class)->execute($advisory);

    expect($advisory->alertDeliveries()->pluck('channel')->sort()->values()->all())->toBe(['in_app', 'sms', 'whatsapp']);
    Bus::assertDispatched(SendAlertDelivery::class, 2);
});

test('external alert channels are not created without farmer consent', function () {
    Bus::fake();
    $farmer = User::factory()->create();
    $farm = Farm::factory()->for($farmer, 'owner')->create();
    FarmerAlertPreference::factory()->for($farmer)->create(['sms_enabled' => true, 'consent' => false, 'consented_at' => null]);
    $advisory = Advisory::factory()->for($farm)->create(['severity' => 'critical']);

    app(QueueAdvisoryAlertDeliveries::class)->execute($advisory);

    expect($advisory->alertDeliveries()->pluck('channel')->all())->toBe(['in_app']);
    Bus::assertNotDispatched(SendAlertDelivery::class);
});
