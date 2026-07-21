<?php

namespace Database\Seeders;

use App\Models\ProductCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductCategorySeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['name' => 'Frutta', 'description' => 'Frutta fresca selezionata, italiana e di importazione.', 'catalog_color' => '#e7f3df'],
            ['name' => 'Verdura', 'description' => 'Ortaggi e verdure fresche per ristorazione e attività commerciali.', 'catalog_color' => '#e3f2e5'],
            ['name' => 'Latticini', 'description' => 'Formaggi freschi, stagionati e specialità casearie.', 'catalog_color' => '#fff1d8'],
            ['name' => 'Prodotti campani', 'description' => 'Eccellenze alimentari e specialità tipiche della Campania.', 'catalog_color' => '#f8e6d8'],
            ['name' => 'Prodotti confezionati', 'description' => 'Prodotti alimentari confezionati per la dispensa professionale.', 'catalog_color' => '#eee6f7'],
        ] as $sortOrder => $category) {
            ProductCategory::updateOrCreate(
                ['name' => $category['name']],
                [
                    'slug' => Str::slug($category['name']),
                    'description' => $category['description'],
                    'catalog_color' => $category['catalog_color'],
                    'is_public' => true,
                    'sort_order' => $sortOrder,
                    'active' => true,
                ],
            );
        }
    }
}
