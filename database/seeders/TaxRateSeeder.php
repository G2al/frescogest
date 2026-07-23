<?php

namespace Database\Seeders;

use App\Models\TaxRate;
use Illuminate\Database\Seeder;

class TaxRateSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['name' => 'Prezzo finale', 'percentage' => 0],
        ] as $taxRate) {
            TaxRate::updateOrCreate(
                ['percentage' => $taxRate['percentage']],
                ['name' => $taxRate['name'], 'active' => true],
            );
        }
    }
}
