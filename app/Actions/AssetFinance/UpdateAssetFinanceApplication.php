<?php

declare(strict_types=1);

namespace App\Actions\AssetFinance;

use App\Enums\AssetFinanceStatus;
use App\Models\AssetFinanceApplication;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class UpdateAssetFinanceApplication
{
    /** @param array<string, mixed> $data */
    public function execute(AssetFinanceApplication $application, User $user, array $data): AssetFinanceApplication
    {
        return DB::transaction(function () use ($application, $user, $data): AssetFinanceApplication {
            $lockedApplication = AssetFinanceApplication::query()->lockForUpdate()->findOrFail($application->getKey());
            $targetStatus = AssetFinanceStatus::from($data['status']);

            if (! $lockedApplication->status->canTransitionTo($targetStatus)) {
                throw ValidationException::withMessages(['status' => "The application cannot move from {$lockedApplication->status->value} to {$targetStatus->value}."]);
            }

            $updates = Arr::only($data, ['status', 'partner_reference', 'decision_note', 'repayment_status', 'outstanding_amount', 'next_payment_due_at']);

            if ($targetStatus === AssetFinanceStatus::UnderReview) {
                $updates['reviewed_at'] = now();
            }
            if ($targetStatus === AssetFinanceStatus::Approved) {
                $updates['approved_at'] = now();
            }
            if ($targetStatus === AssetFinanceStatus::Delivered) {
                $updates['delivery_verified_at'] = now();
                $updates['delivery_verified_by_user_id'] = $user->getKey();
            }
            if (array_key_exists('repayment_status', $updates)) {
                $updates['last_partner_sync_at'] = now();
            }

            $lockedApplication->update($updates);
            $lockedApplication->events()->create([
                'recorded_by_user_id' => $user->getKey(),
                'event_type' => 'status_updated',
                'status' => $targetStatus,
                'note' => $data['decision_note'] ?? null,
                'metadata' => [
                    'partner_reference' => $data['partner_reference'] ?? null,
                    'repayment_status' => $data['repayment_status'] ?? null,
                ],
            ]);

            return $lockedApplication->refresh();
        });
    }
}
