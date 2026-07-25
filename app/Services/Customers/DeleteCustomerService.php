<?php

namespace App\Services\Customers;

use App\Models\Customer;
use Illuminate\Support\Facades\DB;

class DeleteCustomerService
{
    public function delete(Customer $customer): void
    {
        DB::transaction(fn () => $customer->delete());
    }

    public function deleteMany(iterable $customers): void
    {
        DB::transaction(function () use ($customers): void {
            foreach ($customers as $customer) {
                $customer->delete();
            }
        });
    }
}
