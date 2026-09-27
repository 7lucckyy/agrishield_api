<?php

declare(strict_types=1);

use App\Enums\AssetFinanceStatus;
use App\Enums\OrganizationRole;
use App\Enums\RepaymentStatus;
use App\Models\AssetFinanceApplication;
use App\Models\AssetFinanceProduct;
use App\Models\Crop;
use App\Models\CropCycle;
use App\Models\Farm;
use App\Models\FinancePartner;
use App\Models\Organization;
use App\Models\User;

test('an organization administrator submits a consented productive asset application', function () {
    $administrator = User::factory()->create();
    $organization = Organization::factory()->create();
    attachOrganizationRole($administrator, $organization, OrganizationRole::OrganizationAdmin);
    $farm = Farm::factory()->for($organization)->for($administrator, 'owner')->create();
    $cropCycle = CropCycle::factory()->for($farm)->for(Crop::factory())->active()->create();
    $product = AssetFinanceProduct::factory()->for(FinancePartner::factory(), 'financePartner')->create();

    $this->actingAs($administrator)
        ->postJson("/api/v1/organizations/{$organization->getKey()}/asset-finance/applications", [
            'farm_id' => $farm->uuid,
            'farm_crop_cycle_id' => $cropCycle->getKey(),
            'asset_finance_product_id' => $product->getKey(),
            'quantity' => 1,
            'requested_amount' => 850000,
            'purpose' => 'Install solar irrigation equipment for the active maize crop season.',
            'consent' => true,
        ])
        ->assertCreated()
        ->assertJsonPath('data.status', AssetFinanceStatus::Submitted->value)
        ->assertJsonPath('data.farm.id', $farm->uuid)
        ->assertJsonPath('data.repayment.status', RepaymentStatus::NotStarted->value);

    $application = AssetFinanceApplication::query()->sole();
    expect($application->organization_id)->toBe($organization->getKey())
        ->and($application->events()->count())->toBe(1)
        ->and($application->consented_at)->not->toBeNull();
});

test('an asset finance application requires explicit farmer consent', function () {
    $administrator = User::factory()->create();
    $organization = Organization::factory()->create();
    attachOrganizationRole($administrator, $organization, OrganizationRole::OrganizationAdmin);
    $farm = Farm::factory()->for($organization)->for($administrator, 'owner')->create();
    $product = AssetFinanceProduct::factory()->create();

    $this->actingAs($administrator)
        ->postJson("/api/v1/organizations/{$organization->getKey()}/asset-finance/applications", [
            'farm_id' => $farm->uuid,
            'asset_finance_product_id' => $product->getKey(),
            'quantity' => 1,
            'requested_amount' => 500000,
            'purpose' => 'Acquire crop storage equipment for the coming harvest period.',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('consent');
});

test('agronomists can view asset applications but cannot submit them', function () {
    $agronomist = User::factory()->create();
    $organization = Organization::factory()->create();
    attachOrganizationRole($agronomist, $organization, OrganizationRole::Agronomist);
    $farm = Farm::factory()->for($organization)->create();
    $product = AssetFinanceProduct::factory()->create();
    AssetFinanceApplication::factory()->for($organization)->for($farm)->for($agronomist, 'applicant')->for($product, 'product')->create();

    $this->actingAs($agronomist)
        ->getJson("/api/v1/organizations/{$organization->getKey()}/asset-finance/applications")
        ->assertOk()
        ->assertJsonCount(1, 'data');

    $this->actingAs($agronomist)
        ->postJson("/api/v1/organizations/{$organization->getKey()}/asset-finance/applications", [])
        ->assertForbidden();
});

test('verified partner transitions are sequential and retained in the application timeline', function () {
    $administrator = User::factory()->create();
    $organization = Organization::factory()->create();
    attachOrganizationRole($administrator, $organization, OrganizationRole::OrganizationAdmin);
    $farm = Farm::factory()->for($organization)->create();
    $application = AssetFinanceApplication::factory()->for($organization)->for($farm)->for($administrator, 'applicant')->create();
    $endpoint = "/api/v1/organizations/{$organization->getKey()}/asset-finance/applications/{$application->uuid}";

    $this->actingAs($administrator)->patchJson($endpoint, [
        'status' => AssetFinanceStatus::Approved->value,
    ])->assertUnprocessable()->assertJsonValidationErrors('status');

    $this->actingAs($administrator)->patchJson($endpoint, [
        'status' => AssetFinanceStatus::UnderReview->value,
        'partner_reference' => 'BANK-REF-1042',
        'repayment_status' => RepaymentStatus::NotStarted->value,
        'decision_note' => 'Application received by the partner for independent review.',
    ])->assertOk()->assertJsonPath('data.status', AssetFinanceStatus::UnderReview->value);

    expect($application->refresh()->partner_reference)->toBe('BANK-REF-1042')
        ->and($application->events()->where('event_type', 'status_updated')->count())->toBe(1);
});

test('asset finance records are hidden from users outside the organization', function () {
    $organization = Organization::factory()->create();
    $outsider = User::factory()->create();

    $this->actingAs($outsider)
        ->getJson("/api/v1/organizations/{$organization->getKey()}/asset-finance/applications")
        ->assertNotFound();
});

test('the organization workspace labels configured banks as proposed partners', function () {
    $administrator = User::factory()->create();
    $organization = Organization::factory()->create();
    attachOrganizationRole($administrator, $organization, OrganizationRole::OrganizationAdmin);
    $product = AssetFinanceProduct::factory()
        ->for(FinancePartner::factory()->state(['name' => 'Jaiz Bank', 'metadata' => ['relationship_status' => 'proposed']]), 'financePartner')
        ->create(['name' => 'Solar irrigation equipment']);

    $this->actingAs($administrator)
        ->get(route('organization.asset-finance.index', $organization))
        ->assertOk()
        ->assertSee('Asset Access')
        ->assertSee('Jaiz Bank')
        ->assertSee('Proposed')
        ->assertSee($product->name)
        ->assertSee('AgriShield does not approve or issue finance.');
});
