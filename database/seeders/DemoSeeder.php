<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use RuntimeException;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('Demo data can only be seeded in local or testing environments.');
        }

        $this->call([
            DatabaseSeeder::class,
            CompanySeeder::class,
            CustomerSeeder::class,
        ]);
    }
}
