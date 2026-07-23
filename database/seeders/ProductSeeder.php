<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\TaxRate;
use App\Models\UnitOfMeasure;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $categories = ProductCategory::query()->pluck('id', 'name');
        $taxRateId = TaxRate::query()->where('percentage', 0)->value('id');
        $unitId = UnitOfMeasure::query()->where('symbol', 'pz')->value('id');

        foreach ($this->products() as $index => $data) {
            $product = Product::query()->updateOrCreate(
                ['code' => $data['code']],
                [
                    'product_category_id' => $categories->get($data['category']),
                    'tax_rate_id' => $taxRateId,
                    'default_unit_of_measure_id' => $unitId,
                    'name' => $data['name'],
                    'slug' => Str::slug($data['name']),
                    'brand' => $data['brand'],
                    'description' => $data['description'],
                    'public_description' => $data['description'],
                    'price_per_kg' => $data['selling_price'],
                    'purchase_cost_per_unit' => $data['purchase_cost'],
                    'purchase_cost_per_unit_gross' => $data['purchase_cost'],
                    'markup_percentage' => $this->markup($data['purchase_cost'], $data['selling_price']),
                    'restaurant_markup_percentage' => $this->markup($data['purchase_cost'], $data['selling_price']),
                    'base_price_per_unit' => $data['selling_price'],
                    'restaurant_price_per_unit' => $data['selling_price'],
                    'base_minimum_quantity' => 1,
                    'restaurant_minimum_quantity' => 1,
                    'is_public' => true,
                    'is_seasonal' => false,
                    'sort_order' => $index,
                    'active' => true,
                ],
            );

            foreach ($data['variants'] as [$size, $color]) {
                $product->variants()->updateOrCreate(
                    [
                        'size' => $size,
                        'color' => $color,
                    ],
                    [
                        'sku' => $data['code'].'-'.strtoupper(Str::slug($size.'-'.$color)),
                        'active' => true,
                    ],
                );
            }
        }
    }

    private function markup(float $purchaseCost, float $sellingPrice): float
    {
        return round((($sellingPrice - $purchaseCost) / $purchaseCost) * 100, 2);
    }

    private function products(): array
    {
        return [
            [
                'category' => 'T-shirt',
                'name' => 'T-shirt basic girocollo',
                'code' => 'CS-TSH-001',
                'brand' => 'Cerino Store',
                'purchase_cost' => 9.50,
                'selling_price' => 24.90,
                'description' => 'T-shirt da uomo in cotone con vestibilità regolare.',
                'variants' => [['S', 'Bianco'], ['M', 'Bianco'], ['L', 'Bianco'], ['XL', 'Bianco'], ['S', 'Nero'], ['M', 'Nero'], ['L', 'Nero'], ['XL', 'Nero']],
            ],
            [
                'category' => 'T-shirt',
                'name' => 'Polo piqué',
                'code' => 'CS-POL-001',
                'brand' => 'Cerino Store',
                'purchase_cost' => 16.00,
                'selling_price' => 39.90,
                'description' => 'Polo da uomo in cotone piqué con colletto classico.',
                'variants' => [['M', 'Blu navy'], ['L', 'Blu navy'], ['XL', 'Blu navy'], ['M', 'Verde bosco'], ['L', 'Verde bosco'], ['XL', 'Verde bosco']],
            ],
            [
                'category' => 'Camicie',
                'name' => 'Camicia Oxford',
                'code' => 'CS-CAM-001',
                'brand' => 'Cerino Store',
                'purchase_cost' => 22.00,
                'selling_price' => 54.90,
                'description' => 'Camicia Oxford da uomo, versatile e adatta a ogni occasione.',
                'variants' => [['S', 'Bianco'], ['M', 'Bianco'], ['L', 'Bianco'], ['XL', 'Bianco'], ['M', 'Azzurro'], ['L', 'Azzurro'], ['XL', 'Azzurro']],
            ],
            [
                'category' => 'Camicie',
                'name' => 'Camicia lino',
                'code' => 'CS-CAM-002',
                'brand' => 'Cerino Store',
                'purchase_cost' => 25.00,
                'selling_price' => 59.90,
                'description' => 'Camicia leggera in misto lino con vestibilità confortevole.',
                'variants' => [['M', 'Bianco'], ['L', 'Bianco'], ['XL', 'Bianco'], ['M', 'Sabbia'], ['L', 'Sabbia'], ['XL', 'Sabbia']],
            ],
            [
                'category' => 'Pantaloni',
                'name' => 'Jeans slim fit',
                'code' => 'CS-JNS-001',
                'brand' => 'Cerino Store',
                'purchase_cost' => 27.00,
                'selling_price' => 64.90,
                'description' => 'Jeans da uomo slim fit in denim elasticizzato.',
                'variants' => [['44', 'Blu'], ['46', 'Blu'], ['48', 'Blu'], ['50', 'Blu'], ['52', 'Blu']],
            ],
            [
                'category' => 'Pantaloni',
                'name' => 'Pantalone chino',
                'code' => 'CS-PAN-001',
                'brand' => 'Cerino Store',
                'purchase_cost' => 24.00,
                'selling_price' => 59.90,
                'description' => 'Pantalone chino da uomo dalla linea pulita e contemporanea.',
                'variants' => [['44', 'Beige'], ['46', 'Beige'], ['48', 'Beige'], ['50', 'Beige'], ['46', 'Blu navy'], ['48', 'Blu navy'], ['50', 'Blu navy']],
            ],
            [
                'category' => 'Felpe',
                'name' => 'Felpa cappuccio',
                'code' => 'CS-FEL-001',
                'brand' => 'Cerino Store',
                'purchase_cost' => 23.00,
                'selling_price' => 54.90,
                'description' => 'Felpa con cappuccio, tasca a marsupio e interno morbido.',
                'variants' => [['S', 'Grigio'], ['M', 'Grigio'], ['L', 'Grigio'], ['XL', 'Grigio'], ['M', 'Nero'], ['L', 'Nero'], ['XL', 'Nero']],
            ],
            [
                'category' => 'Giacche',
                'name' => 'Giacca monopetto',
                'code' => 'CS-GIA-001',
                'brand' => 'Cerino Store',
                'purchase_cost' => 58.00,
                'selling_price' => 139.90,
                'description' => 'Giacca monopetto da uomo dalla vestibilità moderna.',
                'variants' => [['46', 'Blu navy'], ['48', 'Blu navy'], ['50', 'Blu navy'], ['52', 'Blu navy']],
            ],
            [
                'category' => 'Giacche',
                'name' => 'Giubbino bomber',
                'code' => 'CS-GIU-001',
                'brand' => 'Cerino Store',
                'purchase_cost' => 42.00,
                'selling_price' => 99.90,
                'description' => 'Bomber leggero da uomo con chiusura zip.',
                'variants' => [['M', 'Nero'], ['L', 'Nero'], ['XL', 'Nero'], ['XXL', 'Nero']],
            ],
            [
                'category' => 'Maglieria',
                'name' => 'Maglia girocollo',
                'code' => 'CS-MAG-001',
                'brand' => 'Cerino Store',
                'purchase_cost' => 21.00,
                'selling_price' => 49.90,
                'description' => 'Maglia girocollo da uomo morbida e versatile.',
                'variants' => [['M', 'Panna'], ['L', 'Panna'], ['XL', 'Panna'], ['M', 'Blu navy'], ['L', 'Blu navy'], ['XL', 'Blu navy']],
            ],
            [
                'category' => 'Scarpe',
                'name' => 'Sneaker minimal',
                'code' => 'CS-SNK-001',
                'brand' => 'Cerino Store',
                'purchase_cost' => 39.00,
                'selling_price' => 89.90,
                'description' => 'Sneaker da uomo dal design essenziale.',
                'variants' => [['40', 'Bianco'], ['41', 'Bianco'], ['42', 'Bianco'], ['43', 'Bianco'], ['44', 'Bianco']],
            ],
            [
                'category' => 'Accessori',
                'name' => 'Cintura in pelle',
                'code' => 'CS-ACC-001',
                'brand' => 'Cerino Store',
                'purchase_cost' => 14.00,
                'selling_price' => 34.90,
                'description' => 'Cintura da uomo in vera pelle con fibbia metallica.',
                'variants' => [['90', 'Nero'], ['100', 'Nero'], ['110', 'Nero'], ['90', 'Marrone'], ['100', 'Marrone'], ['110', 'Marrone']],
            ],
        ];
    }
}
