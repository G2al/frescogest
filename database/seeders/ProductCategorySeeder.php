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
            ['name' => 'Frutta', 'description' => 'Frutta fresca selezionata, italiana e di importazione.'],
            ['name' => 'Verdura', 'description' => 'Ortaggi e verdure fresche per ristorazione e attività commerciali.'],
            ['name' => 'Latticini', 'description' => 'Formaggi freschi, stagionati e specialità casearie.'],
            ['name' => 'Prodotti campani', 'description' => 'Eccellenze alimentari e specialità tipiche della Campania.'],
            ['name' => 'Prodotti confezionati', 'description' => 'Prodotti alimentari confezionati per la dispensa professionale.'],
        ] as $sortOrder => $category) {
            ProductCategory::updateOrCreate(
                ['name' => $category['name']],
                [
                    'slug' => Str::slug($category['name']),
                    'description' => $category['description'],
                    'is_public' => true,
                    'sort_order' => $sortOrder,
                    'active' => true,
                ],
            );
        }
    }
}
