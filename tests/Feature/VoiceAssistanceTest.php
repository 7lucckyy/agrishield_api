<?php

declare(strict_types=1);

use App\Enums\OrganizationRole;
use App\Models\Farm;
use App\Models\Organization;
use App\Models\User;
use App\Models\VoiceAssistanceRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('an authenticated user can submit a private voice note and receive guidance', function () {
    Storage::fake('private');
    $user = User::factory()->create();
    $farm = Farm::factory()->for($user, 'owner')->create(['name' => 'North Field']);

    $response = $this->actingAs($user)->post('/api/v1/voice-assistance', [
        'audio' => UploadedFile::fake()->create('question.webm', 120, 'audio/webm'),
        'source_language' => 'ha',
        'response_language' => 'en',
        'farm_id' => $farm->uuid,
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.farm.id', $farm->uuid)
        ->assertJsonPath('data.status', 'completed')
        ->assertJsonPath('data.provider', 'fake');

    $voiceRequest = VoiceAssistanceRequest::query()->sole();
    expect($voiceRequest->user_id)->toBe($user->id)
        ->and($voiceRequest->audio_path)->not->toBeEmpty();
    Storage::disk('private')->assertExists($voiceRequest->audio_path);
});

test('voice cases are private to the user who submitted them', function () {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();
    $voiceRequest = VoiceAssistanceRequest::factory()->for($owner)->create();

    $this->actingAs($stranger)->getJson("/api/v1/voice-assistance/{$voiceRequest->uuid}")->assertForbidden();
    $this->actingAs($stranger)->getJson('/api/v1/voice-assistance')->assertJsonCount(0, 'data');
    $this->actingAs($owner)->getJson("/api/v1/voice-assistance/{$voiceRequest->uuid}")
        ->assertSuccessful()
        ->assertJsonPath('data.id', $voiceRequest->uuid);
});

test('voice intake validates language and audio content type', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post('/api/v1/voice-assistance', [
        'audio' => UploadedFile::fake()->create('notes.txt', 10, 'text/plain'),
        'source_language' => 'unsupported',
        'response_language' => 'auto',
    ])->assertUnprocessable()->assertInvalid(['audio', 'source_language', 'response_language']);
});

test('voice intake limits generated replies to N-ATLaS languages', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post('/api/v1/voice-assistance', [
        'audio' => UploadedFile::fake()->create('question.webm', 100, 'audio/webm'),
        'source_language' => 'ff',
        'response_language' => 'ff',
    ])->assertUnprocessable()->assertInvalid(['response_language']);
});

test('an organization member can use the field voice workspace', function () {
    Storage::fake('private');
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    attachOrganizationRole($user, $organization, OrganizationRole::Agronomist);
    $farm = Farm::factory()->for($user, 'owner')->for($organization)->create(['name' => 'Dry Season Plot']);

    $this->actingAs($user)->get(route('organization.voice-assistance.index', $organization))
        ->assertSuccessful()
        ->assertSee('Listen, translate, respond.')
        ->assertSee('Dry Season Plot');

    $this->actingAs($user)->post(route('organization.voice-assistance.store', $organization), [
        'audio' => UploadedFile::fake()->create('farmer-question.wav', 90, 'audio/wav'),
        'source_language' => 'ff',
        'response_language' => 'en',
        'farm_id' => $farm->id,
    ])->assertRedirect(route('organization.voice-assistance.index', $organization));

    $this->assertDatabaseHas('voice_assistance_requests', [
        'organization_id' => $organization->id,
        'farm_id' => $farm->id,
        'user_id' => $user->id,
    ]);

    $voiceRequest = VoiceAssistanceRequest::query()->sole();
    $this->actingAs($user)->get(route('organization.voice-assistance.audio', [$organization, $voiceRequest]))
        ->assertSuccessful()
        ->assertHeader('content-type', 'audio/wav');
});
