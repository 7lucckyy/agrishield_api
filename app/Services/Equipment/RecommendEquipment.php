<?php

declare(strict_types=1);

namespace App\Services\Equipment;

use App\Enums\AssetCategory;
use App\Enums\EquipmentAcquisitionMode;
use App\Enums\FinancePartnerType;
use App\Models\AssetFinanceProduct;
use App\Models\Farm;
use Illuminate\Database\Eloquent\Collection;

final class RecommendEquipment
{
    /** @return Collection<int, AssetFinanceProduct> */
    public function forFarm(Farm $farm): Collection
    {
        $farm->loadMissing('activeCropCycle.crop')->loadCount([
            'diagnosisRequests',
            'voiceAssistanceRequests',
            'syncRuns',
        ]);

        $hectares = (float) $farm->area_hectares;
        $sizeTier = match (true) {
            $hectares < 2 => 'small',
            $hectares < 10 => 'medium',
            default => 'large',
        };
        $cycle = $farm->activeCropCycle;
        if ($cycle === null) {
            return new Collection;
        }
        $irrigationContext = $cycle->irrigation_context === null ? 'unknown' : $cycle->irrigation_context->value;
        $irrigationOnly = in_array($irrigationContext, ['irrigated', 'mixed'], true);
        $cropCode = $cycle->crop->code;
        $interactionCount = (int) $farm->diagnosis_requests_count
            + (int) $farm->voice_assistance_requests_count
            + (int) $farm->sync_runs_count;

        return AssetFinanceProduct::query()
            ->where('is_active', true)
            ->whereNotNull('metadata->equipment_key')
            ->whereHas('financePartner', fn ($query) => $query->where('is_active', true)->where('type', FinancePartnerType::EquipmentDesk))
            ->get()
            ->filter(function (AssetFinanceProduct $product) use ($sizeTier, $irrigationOnly, $cropCode): bool {
                $metadata = $product->metadata ?? [];
                $isIrrigationTool = in_array($product->category, [
                    AssetCategory::SolarIrrigation,
                    AssetCategory::DripIrrigation,
                    AssetCategory::WaterStorage,
                ], true);

                if ($irrigationOnly !== $isIrrigationTool) {
                    return false;
                }
                if (! in_array($sizeTier, $metadata['farm_size_tiers'] ?? [], true)) {
                    return false;
                }

                $cropCodes = $metadata['crop_codes'] ?? [];

                return $cropCodes === [] || in_array($cropCode, $cropCodes, true);
            })
            ->each(function (AssetFinanceProduct $product) use ($hectares, $sizeTier, $cycle, $irrigationOnly, $cropCode, $interactionCount): void {
                $metadata = $product->metadata ?? [];
                $score = 60;
                $reasons = [number_format($hectares, 1).' hectare '.$sizeTier.' farm'];

                if (in_array($cropCode, $metadata['crop_codes'] ?? [], true)) {
                    $score += 15;
                    $reasons[] = 'Matches your '.$cycle->crop->name.' crop';
                }
                if ($cycle->growth_stage !== null && in_array($cycle->growth_stage, $metadata['growth_stages'] ?? [], true)) {
                    $score += 10;
                    $reasons[] = 'Useful at the '.$cycle->growth_stage.' stage';
                }
                if ($irrigationOnly) {
                    $score += 10;
                    $reasons[] = 'Fits your irrigation setup';
                }
                if ($interactionCount > 0) {
                    $score += min(5, $interactionCount);
                    $reasons[] = 'Uses your recorded farm activity';
                }

                $product->setAttribute('recommendation_score', min(100, $score));
                $product->setAttribute('recommendation_reasons', $reasons);
                $product->setAttribute('farm_size_tier', $sizeTier);
                $product->setAttribute('acquisition_options', $metadata['acquisition_options'] ?? [EquipmentAcquisitionMode::FinancePurchase->value]);
            })
            ->sortByDesc('recommendation_score')
            ->values();
    }
}
