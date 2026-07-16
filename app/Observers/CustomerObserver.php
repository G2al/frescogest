<?php

namespace App\Observers;

use App\Models\Customer;
use App\Services\Pricing\CustomerPriceListService;

class CustomerObserver
{
    public function created(Customer $customer): void
    {
        app(CustomerPriceListService::class)->syncCustomer($customer);
    }
}
