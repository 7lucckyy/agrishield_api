<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\FarmerAlertPreferenceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['in_app_enabled', 'push_enabled', 'sms_enabled', 'whatsapp_enabled', 'voice_call_enabled', 'phone', 'whatsapp_phone', 'language', 'minimum_severity', 'consent', 'consented_at'])]
final class FarmerAlertPreference extends Model
{
    /** @use HasFactory<FarmerAlertPreferenceFactory> */
    use HasFactory;

    protected $attributes = ['in_app_enabled' => true, 'push_enabled' => true, 'sms_enabled' => false, 'whatsapp_enabled' => false, 'voice_call_enabled' => false, 'language' => 'en', 'minimum_severity' => 'medium', 'consent' => false];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return ['in_app_enabled' => 'boolean', 'push_enabled' => 'boolean', 'sms_enabled' => 'boolean', 'whatsapp_enabled' => 'boolean', 'voice_call_enabled' => 'boolean', 'consent' => 'boolean', 'consented_at' => 'datetime'];
    }
}
