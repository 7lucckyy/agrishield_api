<?php

declare(strict_types=1);

use App\Models\AgronomyAdvice;
use App\Models\CropCycle;
use App\Models\Farm;
use App\Models\User;

test('farmer receives and retains farm-specific guidance in a selected Nigerian language', function () {
    config()->set('agronomy.provider', 'fake');
    $farmer = User::factory()->create();
    $farm = Farm::factory()->for($farmer, 'owner')->create(['state' => 'Kano']);
    CropCycle::factory()->for($farm)->active()->create();

    $response = $this->actingAs($farmer)->postJson("/api/v1/farms/{$farm->uuid}/agronomy-advice", [
        'question' => 'How should I inspect this maize field before changing irrigation?',
        'language' => 'ha',
    ])->assertCreated()->assertJsonPath('data.language', 'ha')
        ->assertJsonPath('data.farm_id', $farm->uuid);

    $advice = AgronomyAdvice::query()->where('uuid', $response->json('data.id'))->firstOrFail();
    expect($advice->context_snapshot['state'])->toBe('Kano')
        ->and($advice->next_steps)->not->toBeEmpty();

    $this->actingAs($farmer)->getJson("/api/v1/farms/{$farm->uuid}/agronomy-advice")
        ->assertOk()->assertJsonFragment(['id' => $advice->uuid]);
});

test('agronomy guidance rejects unauthorized farms', function () {
    $owner = User::factory()->create();
    $outsider = User::factory()->create();
    $farm = Farm::factory()->for($owner, 'owner')->create();

    $this->actingAs($outsider)->postJson("/api/v1/farms/{$farm->uuid}/agronomy-advice", [
        'question' => 'What should I inspect before planting?', 'language' => 'en',
    ])->assertForbidden();
});

test('agronomy guidance rejects unsupported languages', function () {
    $owner = User::factory()->create();
    $farm = Farm::factory()->for($owner, 'owner')->create();
    $this->actingAs($owner)->postJson("/api/v1/farms/{$farm->uuid}/agronomy-advice", [
        'question' => 'What should I inspect before planting?', 'language' => 'xx',
    ])->assertUnprocessable()->assertJsonValidationErrors('language');
});
