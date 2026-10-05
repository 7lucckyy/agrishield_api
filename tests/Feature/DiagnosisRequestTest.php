<?php

declare(strict_types=1);

use App\Enums\DiagnosisStatus;
use App\Enums\OrganizationRole;
use App\Integrations\Contracts\CropDiagnosisProvider;
use App\Jobs\PollDiagnosisResult;
use App\Jobs\SubmitDiagnosisToProvider;
use App\Models\AuditLog;
use App\Models\DiagnosisRequest;
use App\Models\Farm;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

beforeEach(function (): void {
    Storage::fake('private');
    Queue::fake();
});

test('a farm viewer uploads a sanitised private image and completes diagnosis with the fake provider', function () {
    $owner = User::factory()->create();
    $farm = Farm::factory()->for($owner, 'owner')->registered()->create();

    $response = $this->actingAs($owner)->post("/api/v1/farms/{$farm->uuid}/diagnosis-requests", [
        'image' => UploadedFile::fake()->image('leaf.jpg', 300, 300),
        'note' => 'Yellow streaks on lower leaves',
    ], ['Accept' => 'application/json'])->assertAccepted()
        ->assertJsonPath('data.status', DiagnosisStatus::Queued->value);

    $diagnosis = DiagnosisRequest::query()->sole();
    Storage::disk('private')->assertExists($diagnosis->image_path);
    expect($diagnosis->image_path)->not->toContain('leaf.jpg')
        ->and($response->json('data.image.url'))->toContain('/api/v1/media/diagnosis/');
    Queue::assertPushed(SubmitDiagnosisToProvider::class);

    (new SubmitDiagnosisToProvider($diagnosis->getKey()))->handle(app(CropDiagnosisProvider::class));
    expect($diagnosis->refresh()->status)->toBe(DiagnosisStatus::Submitted);

    (new PollDiagnosisResult($diagnosis->getKey()))->handle(app(CropDiagnosisProvider::class));
    expect($diagnosis->refresh()->status)->toBe(DiagnosisStatus::Completed)
        ->and($diagnosis->confidence)->toBe('0.8200');
});

test('unsafe polyglot images and undersized images are rejected', function () {
    $owner = User::factory()->create();
    $farm = Farm::factory()->for($owner, 'owner')->create();
    $validImage = UploadedFile::fake()->image('leaf.jpg', 300, 300);
    $polyglot = UploadedFile::fake()->createWithContent('attack.jpg', file_get_contents($validImage->getRealPath()).'<?php echo "owned";');

    $this->actingAs($owner);
    $this->post("/api/v1/farms/{$farm->uuid}/diagnosis-requests", ['image' => $polyglot], ['Accept' => 'application/json'])
        ->assertUnprocessable()->assertJsonValidationErrors('image');
    $this->post("/api/v1/farms/{$farm->uuid}/diagnosis-requests", ['image' => UploadedFile::fake()->image('tiny.jpg', 100, 100)], ['Accept' => 'application/json'])
        ->assertUnprocessable()->assertJsonValidationErrors('image');
});

test('diagnosis retries use one request and reject a reused key with different content', function () {
    $owner = User::factory()->create();
    $farm = Farm::factory()->for($owner, 'owner')->registered()->create();
    $image = UploadedFile::fake()->image('leaf.jpg', 300, 300);
    $key = (string) Str::uuid();
    $url = "/api/v1/farms/{$farm->uuid}/diagnosis-requests";
    $headers = ['Accept' => 'application/json', 'Idempotency-Key' => $key];

    $first = $this->actingAs($owner)->post($url, ['image' => $image, 'note' => 'First observation'], $headers)->assertAccepted();
    $this->post($url, ['image' => $image, 'note' => 'First observation'], $headers)
        ->assertAccepted()
        ->assertJsonPath('data.id', $first->json('data.id'));

    expect(DiagnosisRequest::query()->count())->toBe(1);
    Queue::assertPushed(SubmitDiagnosisToProvider::class, 1);

    $this->post($url, ['image' => $image, 'note' => 'Different observation'], $headers)->assertConflict();
    $this->post($url, ['image' => $image], ['Accept' => 'application/json', 'Idempotency-Key' => 'invalid'])
        ->assertUnprocessable()->assertInvalid('idempotency_key');
});

test('the same farm image submitted by two authorized users creates private requests', function () {
    $organization = Organization::factory()->create();
    $firstUser = User::factory()->create();
    $secondUser = User::factory()->create();
    attachOrganizationRole($firstUser, $organization, OrganizationRole::Agronomist);
    attachOrganizationRole($secondUser, $organization, OrganizationRole::Agronomist);
    $farm = Farm::factory()->for($organization)->for($firstUser, 'owner')->create();
    $image = UploadedFile::fake()->image('same-leaf.jpg', 300, 300);
    $url = "/api/v1/farms/{$farm->uuid}/diagnosis-requests";

    $this->actingAs($firstUser)->post($url, ['image' => $image], ['Accept' => 'application/json'])->assertAccepted();
    Sanctum::actingAs($secondUser, ['*']);
    $this->post($url, ['image' => $image], ['Accept' => 'application/json'])->assertAccepted();

    expect(DiagnosisRequest::query()->count())->toBe(2)
        ->and(DiagnosisRequest::query()->pluck('requested_by_user_id')->all())->toContain($firstUser->getKey(), $secondUser->getKey());
});

test('diagnosis requests become expired after the provider SLA', function () {
    $diagnosis = DiagnosisRequest::factory()->create([
        'status' => DiagnosisStatus::Submitted,
        'expires_at' => now()->subMinute(),
    ]);

    (new PollDiagnosisResult($diagnosis->getKey()))->handle(app(CropDiagnosisProvider::class));

    expect($diagnosis->refresh()->status)->toBe(DiagnosisStatus::Expired);
});

test('organization agronomists override completed diagnoses with an audit trail', function () {
    $organization = Organization::factory()->create();
    $agronomist = User::factory()->create();
    attachOrganizationRole($agronomist, $organization, OrganizationRole::Agronomist);
    $farm = Farm::factory()->for($organization)->create();
    $diagnosis = DiagnosisRequest::factory()->for($farm)->create([
        'status' => DiagnosisStatus::Completed,
        'diagnosis' => 'Original result',
        'recommendation' => 'Original recommendation',
        'confidence' => 0.5,
    ]);

    $this->actingAs($agronomist)->patchJson("/api/v1/farms/{$farm->uuid}/diagnosis-requests/{$diagnosis->uuid}", [
        'diagnosis' => 'Agronomist-reviewed result',
        'recommendation' => 'Apply the reviewed field protocol.',
        'confidence' => 0.95,
    ])->assertSuccessful()
        ->assertJsonPath('data.diagnosis', 'Agronomist-reviewed result')
        ->assertJsonPath('data.reviewed_by', $agronomist->getKey());

    expect(AuditLog::query()->sole()->action)->toBe('diagnosis.overridden');
});

test('an organization agronomist can submit a crop photo from the farm workspace', function () {
    $organization = Organization::factory()->create();
    $agronomist = User::factory()->create();
    attachOrganizationRole($agronomist, $organization, OrganizationRole::Agronomist);
    $farm = Farm::factory()->for($organization)->for($agronomist, 'owner')->create(['name' => 'Kano Maize Plot']);

    $this->actingAs($agronomist)->get(route('organization.farms.show', [$organization, $farm]))
        ->assertSuccessful()
        ->assertSee('Check visible crop symptoms.')
        ->assertSee('No crop screenings yet.');

    $this->actingAs($agronomist)->post(route('organization.farms.diagnoses.store', [$organization, $farm]), [
        'image' => UploadedFile::fake()->image('maize-leaf.jpg', 640, 480),
        'note' => 'Yellow marks on the lower leaves',
    ])->assertRedirect(route('organization.farms.show', [$organization, $farm]));

    $this->assertDatabaseHas('diagnosis_requests', [
        'farm_id' => $farm->id,
        'requested_by_user_id' => $agronomist->id,
        'note' => 'Yellow marks on the lower leaves',
    ]);
    Queue::assertPushed(SubmitDiagnosisToProvider::class);
});
