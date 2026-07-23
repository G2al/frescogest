<?php

namespace Database\Seeders;

use App\Models\UnitOfMeasure;
use Illuminate\Database\Seeder;

class UnitOfMeasureSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['name' => 'Pezzi', 'symbol' => 'pz'],
        ] as $unit) {
            UnitOfMeasure::updateOrCreate(
                ['symbol' => $unit['symbol']],
                ['name' => $unit['name'], 'active' => true],
            );
        }
    }
}
