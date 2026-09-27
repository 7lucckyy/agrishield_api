<?php

declare(strict_types=1);

namespace App\Actions\AssetFinance;

use App\Enums\AssetFinanceStatus;
use App\Enums\RepaymentStatus;
use App\Models\AssetFinanceApplication;
use App\Models\AssetFinanceProduct;
use App\Models\CropCycle;
use App\Models\Farm;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class CreateAssetFinanceApplication
{
    /** @param array<string, mixed> $data */
    public function execute(Organization $organization, Farm $farm, User $applicant, AssetFinanceProduct $product, array $data): AssetFinanceApplication
    {
        if ($farm->organization_id !== $organization->getKey()) {
            throw ValidationException::withMessages(['farm_id' => 'Select a farm in this organization.']);
        }

        if (! $product->is_active || ! $product->financePartner()->where('is_active', true)->exists()) {
            throw ValidationException::withMessages(['asset_finance_product_id' => 'This finance programme is not accepting applications.']);
        }

        $cropCycleId = $data['farm_crop_cycle_id'] ?? null;
        if ($cropCycleId !== null && ! CropCycle::query()->whereKey($cropCycleId)->whereBelongsTo($farm)->exists()) {
            throw ValidationException::withMessages(['farm_crop_cycle_id' => 'Select a crop season attached to this farm.']);
        }

        return DB::transaction(function () use ($organization, $farm, $applicant, $product, $data, $cropCycleId): AssetFinanceApplication {
            $application = new AssetFinanceApplication([
                'uuid' => (string) Str::uuid(),
                'farm_crop_cycle_id' => $cropCycleId,
                'asset_finance_product_id' => $product->getKey(),
                'status' => AssetFinanceStatus::Submitted,
                'quantity' => $data['quantity'],
                'requested_amount' => $data['requested_amount'],
                'purpose' => $data['purpose'],
                'consent_channel' => $data['consent_channel'] ?? 'organization_portal',
                'consent_version' => 'asset-access-v1',
                'consented_at' => now(),
                'submitted_at' => now(),
                'repayment_status' => RepaymentStatus::NotStarted,
            ]);
            $application->organization()->associate($organization);
            $application->farm()->associate($farm);
            $application->applicant()->associate($applicant);
            $application->save();

            $application->events()->create([
                'recorded_by_user_id' => $applicant->getKey(),
                'event_type' => 'application_submitted',
                'status' => AssetFinanceStatus::Submitted,
                'note' => 'Application submitted with recorded farmer consent.',
            ]);

            return $application;
        });
    }
}
