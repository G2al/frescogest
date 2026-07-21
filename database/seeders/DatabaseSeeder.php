<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            CompanySeeder::class,
            ProductCategorySeeder::class,
            TaxRateSeeder::class,
            UnitOfMeasureSeeder::class,
            PaymentMethodSeeder::class,
            CommercialRuleSeeder::class,
            ProductSeeder::class,
            CostCategorySeeder::class,
            CustomerPriceListSeeder::class,
        ]);
    }
}
