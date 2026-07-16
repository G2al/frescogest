<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            ProductCategorySeeder::class,
            TaxRateSeeder::class,
            UnitOfMeasureSeeder::class,
            PaymentMethodSeeder::class,
            ProductSeeder::class,
            CustomerPriceListSeeder::class,
        ]);
    }
}
