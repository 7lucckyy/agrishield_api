<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\FarmerAlertPreferenceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property bool $in_app_enabled
 * @property bool $push_enabled
 * @property bool $sms_enabled
 * @property bool $whatsapp_enabled
 * @property bool $voice_call_enabled
 * @property string|null $phone
 * @property string|null $whatsapp_phone
 * @property string $language
 * @property string $minimum_severity
 * @property bool $consent
 * @property Carbon|null $consented_at
 * @property-read User $user
 */
#[Fillable(['in_app_enabled', 'push_enabled', 'sms_enabled', 'whatsapp_enabled', 'voice_call_enabled', 'phone', 'whatsapp_phone', 'language', 'minimum_severity', 'consent', 'consented_at'])]
final class FarmerAlertPreference extends Model
{
    /** @use HasFactory<FarmerAlertPreferenceFactory> */
    use HasFactory;

    protected $attributes = ['in_app_enabled' => true, 'push_enabled' => true, 'sms_enabled' => false, 'whatsapp_enabled' => false, 'voice_call_enabled' => false, 'language' => 'en', 'minimum_severity' => 'medium', 'consent' => false];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return ['in_app_enabled' => 'boolean', 'push_enabled' => 'boolean', 'sms_enabled' => 'boolean', 'whatsapp_enabled' => 'boolean', 'voice_call_enabled' => 'boolean', 'consent' => 'boolean', 'consented_at' => 'datetime'];
    }
}
