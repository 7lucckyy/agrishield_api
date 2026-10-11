<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\AssetCategory;
use App\Enums\FinancePartnerType;
use App\Models\AssetFinanceProduct;
use App\Models\FinancePartner;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class EquipmentCatalogueSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $desk = FinancePartner::query()->firstOrCreate(
            ['slug' => 'agrishield-equipment-desk'],
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'AgriShield Equipment Desk',
                'legal_name' => 'AgriShield Equipment Desk',
                'type' => FinancePartnerType::EquipmentDesk,
                'financing_model' => 'Equipment requests are reviewed; no financing or leasing terms are promised.',
                'is_active' => true,
                'metadata' => ['relationship_status' => 'internal_request_desk'],
            ],
        );

        $items = [
            ['hand-sprayer', 'Hand pressure sprayer', AssetCategory::Mechanization, ['small'], ['finance_purchase'], 'Portable crop treatment tool', 'Backpack'],
            ['motor-sprayer', 'Motorised sprayer', AssetCategory::Mechanization, ['medium', 'large'], ['finance_purchase'], 'Faster crop treatment for wider fields', 'Motorised'],
            ['mini-tiller', 'Compact power tiller', AssetCategory::Mechanization, ['small', 'medium'], ['lease', 'finance_purchase'], 'Land preparation for smaller plots', 'Compact'],
            ['tractor-lease', 'Tractor', AssetCategory::Mechanization, ['large'], ['lease', 'finance_purchase'], 'Large-field land preparation and hauling', 'Full size'],
            ['solar-pump-small', 'Solar water pump', AssetCategory::SolarIrrigation, ['small', 'medium'], ['lease', 'finance_purchase'], 'Water lifting for irrigated fields', 'Small field'],
            ['solar-pump-large', 'High-capacity solar pump', AssetCategory::SolarIrrigation, ['large'], ['lease', 'finance_purchase'], 'Water lifting for larger irrigated fields', 'Large field'],
            ['drip-kit', 'Drip irrigation kit', AssetCategory::DripIrrigation, ['small', 'medium'], ['finance_purchase'], 'Controlled water delivery near crops', 'Small field'],
            ['drip-system-large', 'Large-field drip system', AssetCategory::DripIrrigation, ['large'], ['lease', 'finance_purchase'], 'Controlled water delivery across larger acreage', 'Large field'],
            ['water-tank', 'Water storage tank', AssetCategory::WaterStorage, ['small', 'medium'], ['finance_purchase'], 'On-farm water storage', 'Portable'],
            ['water-reservoir', 'Large water storage system', AssetCategory::WaterStorage, ['large'], ['lease', 'finance_purchase'], 'Water storage for larger irrigated farms', 'Large field'],
        ];

        foreach ($items as [$key, $name, $category, $tiers, $options, $description, $capacity]) {
            AssetFinanceProduct::query()->firstOrCreate(
                ['finance_partner_id' => $desk->getKey(), 'name' => $name],
                [
                    'category' => $category,
                    'financing_structure' => 'Request for review; terms are confirmed before any agreement.',
                    'currency' => 'NGN',
                    'eligibility_summary' => 'Available to request for a registered farm; availability and terms are confirmed after review.',
                    'is_active' => true,
                    'metadata' => [
                        'equipment_key' => $key,
                        'farm_size_tiers' => $tiers,
                        'acquisition_options' => $options,
                        'description' => $description,
                        'capacity' => $capacity,
                    ],
                ],
            );
        }
    }
}
