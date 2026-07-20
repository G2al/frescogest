<?php

namespace Database\Seeders;

use App\Models\CostCategory;
use Illuminate\Database\Seeder;

class CostCategorySeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Carburante', 'Assicurazione', 'Cibo', 'Casello', 'Manutenzione auto'] as $name) {
            CostCategory::query()->updateOrCreate(['name' => $name], ['is_monthly' => false, 'active' => true]);
        }

        CostCategory::query()->updateOrCreate(['name' => 'Stipendi'], ['is_monthly' => true, 'active' => true]);
    }
}
