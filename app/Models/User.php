<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\OrganizationMembershipStatus;
use App\Enums\OrganizationRole;
use App\Enums\UserStatus;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property string $name
 * @property string|null $email
 * @property string|null $phone
 * @property string $password
 * @property UserStatus $status
 * @property string $locale
 * @property Carbon|null $last_login_at
 * @property Carbon|null $created_at
 * @property-read Collection<int, Organization> $organizations
 * @property-read Collection<int, Farm> $farms
 * @property-read Collection<int, VoiceAssistanceRequest> $voiceAssistanceRequests
 * @property-read OrganizationMembership $membership
 */
#[Fillable(['name', 'email', 'phone', 'password', 'locale'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable, SoftDeletes;

    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => 'active',
        'locale' => 'en',
    ];

    /** @return BelongsToMany<Organization, $this, OrganizationMembership, 'membership'> */
    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class)
            ->using(OrganizationMembership::class)
            ->as('membership')
            ->withPivot(['role', 'cluster_name', 'status', 'joined_at'])
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
        return $this->hasMany(Farm::class, 'owner_user_id');
    }

    /** @return HasMany<VoiceAssistanceRequest, $this> */
    public function voiceAssistanceRequests(): HasMany
    {
        return $this->hasMany(VoiceAssistanceRequest::class);
    }

    public function belongsToOrganization(Organization|int $organization): bool
    {
        $organizationId = $organization instanceof Organization
            ? $organization->getKey()
            : $organization;

        return in_array(
            $organizationId,
            $this->organizationIdsWhereRoleIn(OrganizationRole::cases()),
            true,
        );
    }

    /** @param OrganizationRole|array<array-key, OrganizationRole> $roles */
    public function hasOrganizationRole(
        Organization|int $organization,
        OrganizationRole|array $roles,
    ): bool {
        $organizationId = $organization instanceof Organization
            ? $organization->getKey()
            : $organization;

        return in_array(
            $organizationId,
            $this->organizationIdsWhereRoleIn(is_array($roles) ? $roles : [$roles]),
            true,
        );
    }

    /**
     * @param  array<array-key, OrganizationRole>  $roles
     * @return list<int>
     */
    public function organizationIdsWhereRoleIn(array $roles): array
    {
        $roleValues = collect($roles)
            ->map(fn (OrganizationRole $role): string => $role->value)
            ->unique()
            ->sort()
            ->values()
            ->all();

        if ($roleValues === []) {
            return [];
        }

        return once(function () use ($roleValues): array {
            $organizations = $this->organizations();
            $qualifiedOrganizationKey = $organizations->getRelated()
                ->qualifyColumn($organizations->getRelated()->getKeyName());

            return $organizations
                ->wherePivot('status', OrganizationMembershipStatus::Active->value)
                ->wherePivotIn('role', $roleValues)
                ->pluck($qualifiedOrganizationKey)
                ->map(fn (mixed $organizationId): int => (int) $organizationId)
                ->all();
        });
    }

    public function primaryOrganizationId(): ?int
    {
        $organization = $this->organizations()
            ->wherePivot('status', OrganizationMembershipStatus::Active->value)
            ->orderByPivot('joined_at')
            ->orderByPivot('id')
            ->first();

        return $organization?->getKey();
    }

    public function leadsFarmCluster(Farm $farm): bool
    {
        if ($farm->organization_id === null) {
            return false;
        }

        $leadCluster = OrganizationMembership::query()
            ->where('organization_id', $farm->organization_id)
            ->where('user_id', $this->getKey())
            ->where('status', OrganizationMembershipStatus::Active->value)
            ->where('role', OrganizationRole::ClusterLead->value)
            ->value('cluster_name');

        if (! is_string($leadCluster) || $leadCluster === '') {
            return false;
        }

        return OrganizationMembership::query()
            ->where('organization_id', $farm->organization_id)
            ->where('user_id', $farm->owner_user_id)
            ->where('status', OrganizationMembershipStatus::Active->value)
            ->where('cluster_name', $leadCluster)
            ->exists();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'status' => UserStatus::class,
        ];
    }
}
