<?php

namespace Database\Seeders;

use App\Models\ProductCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductCategorySeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->categories() as $index => $category) {
            ProductCategory::query()->updateOrCreate(
                ['name' => $category['name']],
                [
                    'slug' => Str::slug($category['name']),
                    'description' => $category['description'],
                    'catalog_color' => $category['color'],
                    'is_public' => true,
                    'sort_order' => $index,
                    'active' => true,
                ],
            );
        }
    }

    private function categories(): array
    {
        return [
            ['name' => 'T-shirt', 'description' => 'T-shirt e polo da uomo.', 'color' => '#f1f1f1'],
            ['name' => 'Camicie', 'description' => 'Camicie casual ed eleganti.', 'color' => '#e8edf2'],
            ['name' => 'Pantaloni', 'description' => 'Jeans, chino e pantaloni da uomo.', 'color' => '#eee9e2'],
            ['name' => 'Felpe', 'description' => 'Felpe girocollo e con cappuccio.', 'color' => '#e8e8e8'],
            ['name' => 'Maglieria', 'description' => 'Maglie e cardigan da uomo.', 'color' => '#ede7df'],
            ['name' => 'Giacche', 'description' => 'Giacche, blazer e capispalla.', 'color' => '#dfe4e8'],
            ['name' => 'Scarpe', 'description' => 'Calzature da uomo.', 'color' => '#eeeae5'],
            ['name' => 'Accessori', 'description' => 'Accessori per completare ogni outfit.', 'color' => '#e9e9e9'],
        ];
    }
}
