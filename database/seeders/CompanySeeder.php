<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        Company::query()->updateOrCreate(
            ['vat_number' => '02396610186'],
            [
                'business_name' => 'Il Paradiso della Frutta di Castaldo Mariarosaria',
                'tax_code' => null,
                'email' => null,
                'phone' => null,
                'address' => 'Via dei Caduti Genovesi, 8',
                'city' => 'Bornasco',
                'postal_code' => null,
                'province' => 'PV',
                'iban' => null,
                'logo_path' => null,
                'active' => true,
            ],
        );
    }
}
