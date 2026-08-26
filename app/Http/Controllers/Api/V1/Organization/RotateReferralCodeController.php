<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Organization;

use App\Actions\Audit\RecordAuditLog;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\OrganizationResource;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

final class RotateReferralCodeController extends Controller
{
    public function __invoke(Request $request, Organization $organization, RecordAuditLog $recordAuditLog): OrganizationResource
    {
        Gate::authorize('rotateReferralCode', $organization);
        $organization->forceFill([
            'referral_code' => Str::upper(Str::random(12)),
            'referral_code_expires_at' => now()->addYear(),
        ])->save();
        $recordAuditLog->execute('referral.rotated', $organization, ['after' => ['referral_code_expires_at' => $organization->referral_code_expires_at?->toISOString()]]);

        return new OrganizationResource($organization);
    }
}
