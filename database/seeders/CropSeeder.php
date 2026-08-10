<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\CropCategory;
use App\Models\Crop;
use App\Services\CropCatalogueCache;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

final class CropSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(CropCatalogueCache $cropCatalogueCache): void
    {
        $timestamp = now();
        $crops = [
            ['name' => 'Maize', 'scientific_name' => 'Zea mays', 'code' => 'MAIZE', 'category' => CropCategory::Cereal->value, 'default_cycle_days' => 120],
            ['name' => 'Rice', 'scientific_name' => 'Oryza sativa', 'code' => 'RICE', 'category' => CropCategory::Cereal->value, 'default_cycle_days' => 120],
            ['name' => 'Sorghum', 'scientific_name' => 'Sorghum bicolor', 'code' => 'SORGHUM', 'category' => CropCategory::Cereal->value, 'default_cycle_days' => 120],
            ['name' => 'Pearl Millet', 'scientific_name' => 'Cenchrus americanus', 'code' => 'PEARL_MILLET', 'category' => CropCategory::Cereal->value, 'default_cycle_days' => 90],
            ['name' => 'Wheat', 'scientific_name' => 'Triticum aestivum', 'code' => 'WHEAT', 'category' => CropCategory::Cereal->value, 'default_cycle_days' => 120],
            ['name' => 'Fonio', 'scientific_name' => 'Digitaria exilis', 'code' => 'FONIO', 'category' => CropCategory::Cereal->value, 'default_cycle_days' => 90],
            ['name' => 'Cowpea', 'scientific_name' => 'Vigna unguiculata', 'code' => 'COWPEA', 'category' => CropCategory::Pulse->value, 'default_cycle_days' => 75],
            ['name' => 'Soybean', 'scientific_name' => 'Glycine max', 'code' => 'SOYBEAN', 'category' => CropCategory::Pulse->value, 'default_cycle_days' => 110],
            ['name' => 'Groundnut', 'scientific_name' => 'Arachis hypogaea', 'code' => 'GROUNDNUT', 'category' => CropCategory::Oilseed->value, 'default_cycle_days' => 110],
            ['name' => 'Common Bean', 'scientific_name' => 'Phaseolus vulgaris', 'code' => 'COMMON_BEAN', 'category' => CropCategory::Pulse->value, 'default_cycle_days' => 90],
            ['name' => 'Pigeon Pea', 'scientific_name' => 'Cajanus cajan', 'code' => 'PIGEON_PEA', 'category' => CropCategory::Pulse->value, 'default_cycle_days' => 150],
            ['name' => 'Chickpea', 'scientific_name' => 'Cicer arietinum', 'code' => 'CHICKPEA', 'category' => CropCategory::Pulse->value, 'default_cycle_days' => 110],
            ['name' => 'Bambara Groundnut', 'scientific_name' => 'Vigna subterranea', 'code' => 'BAMBARA_GROUNDNUT', 'category' => CropCategory::Pulse->value, 'default_cycle_days' => 120],
            ['name' => 'Sesame', 'scientific_name' => 'Sesamum indicum', 'code' => 'SESAME', 'category' => CropCategory::Oilseed->value, 'default_cycle_days' => 100],
            ['name' => 'Sunflower', 'scientific_name' => 'Helianthus annuus', 'code' => 'SUNFLOWER', 'category' => CropCategory::Oilseed->value, 'default_cycle_days' => 110],
            ['name' => 'Cassava', 'scientific_name' => 'Manihot esculenta', 'code' => 'CASSAVA', 'category' => CropCategory::Other->value, 'default_cycle_days' => 365],
            ['name' => 'Yam', 'scientific_name' => 'Dioscorea rotundata', 'code' => 'YAM', 'category' => CropCategory::Other->value, 'default_cycle_days' => 270],
            ['name' => 'Sweet Potato', 'scientific_name' => 'Ipomoea batatas', 'code' => 'SWEET_POTATO', 'category' => CropCategory::Other->value, 'default_cycle_days' => 120],
            ['name' => 'Potato', 'scientific_name' => 'Solanum tuberosum', 'code' => 'POTATO', 'category' => CropCategory::Other->value, 'default_cycle_days' => 110],
            ['name' => 'Cocoyam', 'scientific_name' => 'Colocasia esculenta', 'code' => 'COCOYAM', 'category' => CropCategory::Other->value, 'default_cycle_days' => 240],
            ['name' => 'Tomato', 'scientific_name' => 'Solanum lycopersicum', 'code' => 'TOMATO', 'category' => CropCategory::Vegetable->value, 'default_cycle_days' => 100],
            ['name' => 'Pepper', 'scientific_name' => 'Capsicum annuum', 'code' => 'PEPPER', 'category' => CropCategory::Vegetable->value, 'default_cycle_days' => 120],
            ['name' => 'Okra', 'scientific_name' => 'Abelmoschus esculentus', 'code' => 'OKRA', 'category' => CropCategory::Vegetable->value, 'default_cycle_days' => 60],
            ['name' => 'Onion', 'scientific_name' => 'Allium cepa', 'code' => 'ONION', 'category' => CropCategory::Vegetable->value, 'default_cycle_days' => 120],
            ['name' => 'Cabbage', 'scientific_name' => 'Brassica oleracea var. capitata', 'code' => 'CABBAGE', 'category' => CropCategory::Vegetable->value, 'default_cycle_days' => 100],
            ['name' => 'Carrot', 'scientific_name' => 'Daucus carota subsp. sativus', 'code' => 'CARROT', 'category' => CropCategory::Vegetable->value, 'default_cycle_days' => 90],
            ['name' => 'Cucumber', 'scientific_name' => 'Cucumis sativus', 'code' => 'CUCUMBER', 'category' => CropCategory::Vegetable->value, 'default_cycle_days' => 60],
            ['name' => 'Watermelon', 'scientific_name' => 'Citrullus lanatus', 'code' => 'WATERMELON', 'category' => CropCategory::Fruit->value, 'default_cycle_days' => 90],
            ['name' => 'Pumpkin', 'scientific_name' => 'Cucurbita moschata', 'code' => 'PUMPKIN', 'category' => CropCategory::Vegetable->value, 'default_cycle_days' => 110],
            ['name' => 'Eggplant', 'scientific_name' => 'Solanum melongena', 'code' => 'EGGPLANT', 'category' => CropCategory::Vegetable->value, 'default_cycle_days' => 120],
            ['name' => 'Amaranth', 'scientific_name' => 'Amaranthus cruentus', 'code' => 'AMARANTH', 'category' => CropCategory::Vegetable->value, 'default_cycle_days' => 45],
            ['name' => 'Plantain', 'scientific_name' => 'Musa × paradisiaca', 'code' => 'PLANTAIN', 'category' => CropCategory::Fruit->value, 'default_cycle_days' => 365],
            ['name' => 'Banana', 'scientific_name' => 'Musa acuminata', 'code' => 'BANANA', 'category' => CropCategory::Fruit->value, 'default_cycle_days' => 365],
            ['name' => 'Mango', 'scientific_name' => 'Mangifera indica', 'code' => 'MANGO', 'category' => CropCategory::Fruit->value, 'default_cycle_days' => 365],
            ['name' => 'Orange', 'scientific_name' => 'Citrus sinensis', 'code' => 'ORANGE', 'category' => CropCategory::Fruit->value, 'default_cycle_days' => 365],
            ['name' => 'Pineapple', 'scientific_name' => 'Ananas comosus', 'code' => 'PINEAPPLE', 'category' => CropCategory::Fruit->value, 'default_cycle_days' => 540],
            ['name' => 'Papaya', 'scientific_name' => 'Carica papaya', 'code' => 'PAPAYA', 'category' => CropCategory::Fruit->value, 'default_cycle_days' => 300],
            ['name' => 'Cashew', 'scientific_name' => 'Anacardium occidentale', 'code' => 'CASHEW', 'category' => CropCategory::Fruit->value, 'default_cycle_days' => 730],
            ['name' => 'Cocoa', 'scientific_name' => 'Theobroma cacao', 'code' => 'COCOA', 'category' => CropCategory::Other->value, 'default_cycle_days' => 730],
            ['name' => 'Cotton', 'scientific_name' => 'Gossypium hirsutum', 'code' => 'COTTON', 'category' => CropCategory::Fibre->value, 'default_cycle_days' => 180],
            ['name' => 'Sugarcane', 'scientific_name' => 'Saccharum officinarum', 'code' => 'SUGARCANE', 'category' => CropCategory::Other->value, 'default_cycle_days' => 365],
        ];

        $records = array_map(fn (array $crop): array => [
            ...$crop,
            'active' => true,
            'metadata' => null,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ], $crops);

        Crop::query()->upsert(
            $records,
            ['code'],
            ['name', 'scientific_name', 'category', 'default_cycle_days', 'updated_at'],
        );

        $cropCatalogueCache->invalidate();
    }
}
