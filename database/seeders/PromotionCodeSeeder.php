<?php

namespace Database\Seeders;

use App\Enums\PromotionAudience;
use App\Enums\PromotionRule;
use App\Models\PromotionCode;
use Illuminate\Database\Seeder;

class PromotionCodeSeeder extends Seeder
{
    public function run(): void
    {
        PromotionCode::query()->updateOrCreate(
            ['code' => 'PARADISO10'],
            [
                'name' => 'Benvenuto primo ordine',
                'discount_percentage' => 10,
                'audience' => PromotionAudience::Everyone,
                'rule' => PromotionRule::FirstOrder,
                'single_use_per_customer' => true,
                'active' => true,
            ],
        );
    }
}
