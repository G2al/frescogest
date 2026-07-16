<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Services\Pricing\CustomerPriceListService;
use Illuminate\Database\Seeder;

class CustomerPriceListSeeder extends Seeder
{
    public function run(CustomerPriceListService $priceLists): void
    {
        Customer::query()->each(fn (Customer $customer) => $priceLists->syncCustomer($customer));
    }
}
