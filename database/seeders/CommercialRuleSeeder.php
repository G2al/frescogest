<?php

namespace Database\Seeders;

use App\Enums\CustomerType;
use App\Models\CommercialRule;
use App\Models\TaxRate;
use Illuminate\Database\Seeder;

class CommercialRuleSeeder extends Seeder
{
    public function run(): void
    {
        CommercialRule::query()->updateOrCreate(
            ['name' => 'Privati - regola nazionale'],
            [
                'customer_type' => CustomerType::Private,
                'province' => null,
                'minimum_order_gross' => 50,
                'free_shipping_threshold_gross' => 100,
                'shipping_fee_net' => 5,
                'shipping_tax_rate_id' => TaxRate::query()->where('percentage', 22)->value('id'),
                'priority' => 0,
                'active' => true,
            ],
        );
    }
}
