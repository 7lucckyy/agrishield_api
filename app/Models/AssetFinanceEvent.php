<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AssetFinanceStatus;
use Database\Factories\AssetFinanceEventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['asset_finance_application_id', 'recorded_by_user_id', 'event_type', 'status', 'note', 'metadata'])]
final class AssetFinanceEvent extends Model
{
    /** @use HasFactory<AssetFinanceEventFactory> */
    use HasFactory;

    /** @return BelongsTo<AssetFinanceApplication, $this> */
    public function application(): BelongsTo
    {
        return $this->belongsTo(AssetFinanceApplication::class, 'asset_finance_application_id');
    }

    /** @return BelongsTo<User, $this> */
    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => AssetFinanceStatus::class,
            'metadata' => 'array',
        ];
    }
}
