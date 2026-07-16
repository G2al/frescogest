<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Contanti', 'Bonifico'] as $name) {
            PaymentMethod::updateOrCreate(
                ['name' => $name],
                ['active' => true],
            );
        }
    }
}
