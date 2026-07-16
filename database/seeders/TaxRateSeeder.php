<?php

namespace Database\Seeders;

use App\Models\TaxRate;
use Illuminate\Database\Seeder;

class TaxRateSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['name' => 'IVA 4%', 'percentage' => 4],
            ['name' => 'IVA 10%', 'percentage' => 10],
            ['name' => 'IVA 22%', 'percentage' => 22],
        ] as $taxRate) {
            TaxRate::updateOrCreate(
                ['percentage' => $taxRate['percentage']],
                ['name' => $taxRate['name'], 'active' => true],
            );
        }
    }
}
