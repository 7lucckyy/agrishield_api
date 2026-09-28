<?php

namespace App\Actions\Auth;

use App\Data\Auth\AuthenticationResult;
use App\Enums\OrganizationMembershipStatus;
use App\Enums\OrganizationRole;
use App\Models\User;
use App\Services\Referral\ReferralCodeResolver;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RegisterUser
{
    public function __construct(private ReferralCodeResolver $referralCodeResolver) {}

    /** @param array<string, mixed> $data */
    public function execute(array $data, ?string $ipAddress, ?string $userAgent): AuthenticationResult
    {
        return DB::transaction(function () use ($data, $ipAddress, $userAgent): AuthenticationResult {
            $organization = $this->referralCodeResolver->resolve(Arr::get($data, 'referral_code'));

            if (Arr::get($data, 'referral_code') !== null && $organization === null) {
                throw ValidationException::withMessages([
                    'referral_code' => ['The referral code is invalid or no longer active.'],
                ]);
            }

            $user = User::query()->create(Arr::only($data, [
                'name',
                'phone',
                'password',
                'locale',
            ]));

            if ($organization !== null) {
                $user->organizations()->attach($organization->getKey(), [
                    'role' => OrganizationRole::Farmer->value,
                    'status' => OrganizationMembershipStatus::Active->value,
                    'joined_at' => now(),
                ]);

                $user->referralRedemptions()->create([
                    'organization_id' => $organization->getKey(),
                    'referral_code' => $organization->referral_code,
                    'ip_address' => $ipAddress,
                    'user_agent' => $userAgent,
                    'redeemed_at' => now(),
                ]);
            }

            $user->load('organizations');
            $token = $user->createToken('mobile', ['*']);

            return new AuthenticationResult($user, $token->plainTextToken);
        });
    }
}
