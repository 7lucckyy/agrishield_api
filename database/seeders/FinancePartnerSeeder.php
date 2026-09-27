<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\AssetCategory;
use App\Enums\FinancePartnerType;
use App\Models\AssetFinanceProduct;
use App\Models\FinancePartner;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class FinancePartnerSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $partners = [
            [
                'name' => 'Jaiz Bank',
                'legal_name' => 'Jaiz Bank Plc',
                'slug' => 'jaiz-bank',
                'type' => FinancePartnerType::NonInterestBank,
                'financing_model' => 'Non-interest asset finance subject to Jaiz Bank approval',
                'website' => 'https://www.jaizbankplc.com/',
                'products' => [
                    ['name' => 'Solar irrigation equipment', 'category' => AssetCategory::SolarIrrigation, 'structure' => 'Proposed Murabahah or Ijarah structure'],
                    ['name' => 'Crop storage equipment', 'category' => AssetCategory::HermeticStorage, 'structure' => 'Proposed Murabahah structure'],
                ],
            ],
            [
                'name' => 'TAJBank',
                'legal_name' => 'TAJBank Limited',
                'slug' => 'tajbank',
                'type' => FinancePartnerType::NonInterestBank,
                'financing_model' => 'Non-interest asset finance subject to TAJBank approval',
                'website' => 'https://tajbank.com/',
                'products' => [
                    ['name' => 'Drip irrigation equipment', 'category' => AssetCategory::DripIrrigation, 'structure' => 'Proposed Murabahah or Ijarah structure'],
                    ['name' => 'Small-scale crop processing equipment', 'category' => AssetCategory::ProcessingEquipment, 'structure' => 'Proposed Ijarah structure'],
                ],
            ],
            [
                'name' => 'Bank of Agriculture',
                'legal_name' => 'Bank of Agriculture Limited',
                'slug' => 'bank-of-agriculture',
                'type' => FinancePartnerType::DevelopmentFinanceInstitution,
                'financing_model' => 'Agricultural credit subject to Bank of Agriculture approval',
                'website' => 'https://boanig.com/',
                'products' => [
                    ['name' => 'Crop production mechanization', 'category' => AssetCategory::Mechanization, 'structure' => 'Proposed agricultural asset loan'],
                    ['name' => 'Water storage and irrigation equipment', 'category' => AssetCategory::WaterStorage, 'structure' => 'Proposed agricultural asset loan'],
                ],
            ],
        ];

        foreach ($partners as $partnerData) {
            $products = $partnerData['products'];
            unset($partnerData['products']);
            $existingUuid = FinancePartner::query()->where('slug', $partnerData['slug'])->value('uuid');

            $partner = FinancePartner::query()->updateOrCreate(
                ['slug' => $partnerData['slug']],
                [
                    ...$partnerData,
                    'uuid' => $existingUuid ?? (string) Str::uuid(),
                    'is_active' => true,
                    'metadata' => [
                        'relationship_status' => 'proposed',
                        'disclaimer' => 'Configuration does not represent an executed partnership or approved finance product.',
                    ],
                ],
            );

            foreach ($products as $product) {
                AssetFinanceProduct::query()->updateOrCreate(
                    ['finance_partner_id' => $partner->getKey(), 'name' => $product['name']],
                    [
                        'category' => $product['category'],
                        'financing_structure' => $product['structure'],
                        'currency' => 'NGN',
                        'eligibility_summary' => 'Registered crop farm, active crop season, recorded farmer consent and all identity, credit and eligibility checks required by the finance partner.',
                        'is_active' => true,
                        'metadata' => ['terms_status' => 'to_be_agreed'],
                    ],
                );
            }
        }
    }
}
