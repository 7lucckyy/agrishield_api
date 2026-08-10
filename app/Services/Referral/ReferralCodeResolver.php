<?php

namespace App\Services\Referral;

use App\Enums\OrganizationStatus;
use App\Models\Organization;
use Illuminate\Support\Str;

class ReferralCodeResolver
{
    public function resolve(?string $referralCode): ?Organization
    {
        if ($referralCode === null || $referralCode === '') {
            return null;
        }

        return Organization::query()
            ->where('referral_code', Str::upper($referralCode))
            ->where('status', OrganizationStatus::Active)
            ->where(function ($query): void {
                $query->whereNull('referral_code_expires_at')
                    ->orWhere('referral_code_expires_at', '>', now());
            })
            ->first();
    }
}
