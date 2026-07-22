<?php

namespace Database\Seeders;

use App\Models\UnitOfMeasure;
use Illuminate\Database\Seeder;

class UnitOfMeasureSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['name' => 'Chilogrammi', 'symbol' => 'kg'],
            ['name' => 'Grammi', 'symbol' => 'g'],
            ['name' => 'Pezzi', 'symbol' => 'pz'],
            ['name' => 'Buste', 'symbol' => 'busta'],
            ['name' => 'Vaschette', 'symbol' => 'vaschetta'],
            ['name' => 'Cestini', 'symbol' => 'cestino'],
            ['name' => 'Casse', 'symbol' => 'cassa'],
            ['name' => 'Confezioni', 'symbol' => 'conf'],
            ['name' => 'Cartoni', 'symbol' => 'cartone'],
            ['name' => 'Coppie', 'symbol' => 'coppia'],
            ['name' => 'File', 'symbol' => 'fila'],
        ] as $unit) {
            UnitOfMeasure::updateOrCreate(
                ['symbol' => $unit['symbol']],
                ['name' => $unit['name'], 'active' => true],
            );
        }
    }
}
