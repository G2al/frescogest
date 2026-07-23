<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        Company::query()->updateOrCreate(
            ['business_name' => 'Cerino Store'],
            [
                'vat_number' => null,
                'tax_code' => null,
                'email' => 'admin@cerinostore.it',
                'phone' => '3240994144',
                'address' => 'Viale Colucci, 49',
                'city' => 'Lusciano',
                'postal_code' => '81030',
                'province' => 'CE',
                'iban' => null,
                'logo_path' => null,
                'active' => true,
            ],
        );
    }
}
