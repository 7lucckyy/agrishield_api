<?php

namespace App\Models;

use App\Enums\OrganizationStatus;
use Database\Factories\OrganizationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property OrganizationStatus $status
 * @property string|null $contact_email
 * @property string|null $contact_phone
 * @property string|null $country
 * @property string|null $referral_code
 * @property Carbon|null $referral_code_expires_at
 * @property Carbon|null $created_at
 * @property-read OrganizationMembership $membership
 * @property-read Collection<int, User> $users
 * @property-read Collection<int, Farm> $farms
 * @property-read Collection<int, VoiceAssistanceRequest> $voiceAssistanceRequests
 */
#[Fillable(['name', 'slug', 'referral_code', 'referral_code_expires_at', 'status', 'contact_email', 'contact_phone', 'country', 'metadata'])]
class Organization extends Model
{
    /** @use HasFactory<OrganizationFactory> */
    use HasFactory, SoftDeletes;

    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => 'active',
    ];

    /** @return BelongsToMany<User, $this, OrganizationMembership, 'membership'> */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->using(OrganizationMembership::class)
            ->as('membership')
            ->withPivot(['role', 'status', 'joined_at'])
            ->withTimestamps();
    }

    /** @return HasMany<ReferralRedemption, $this> */
    public function referralRedemptions(): HasMany
    {
        return $this->hasMany(ReferralRedemption::class);
    }

    /** @return HasMany<Farm, $this> */
    public function farms(): HasMany
    {
        return $this->hasMany(Farm::class);
    }

    /** @return HasMany<VoiceAssistanceRequest, $this> */
    public function voiceAssistanceRequests(): HasMany
    {
        return $this->hasMany(VoiceAssistanceRequest::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'referral_code_expires_at' => 'datetime',
            'status' => OrganizationStatus::class,
            'metadata' => 'array',
        ];
    }
}
