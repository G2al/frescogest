<?php

namespace App\Services\Customers;

use App\Models\Customer;
use App\Services\Orders\DeleteOrderService;
use Illuminate\Support\Facades\DB;

class DeleteCustomerService
{
    public function __construct(
        private readonly DeleteOrderService $deleteOrderService,
    ) {}

    public function delete(Customer $customer): void
    {
        DB::transaction(function () use ($customer): void {
            $user = $customer->user()->first();

            $this->deleteOrderService->deleteMany(
                $customer->orders()->with('deliveryDocument')->get(),
            );

            $customer->delete();

            if ($user === null) {
                return;
            }

            DB::table('sessions')->where('user_id', $user->id)->delete();
            DB::table('password_reset_tokens')->where('email', $user->email)->delete();

            $user->delete();
        });
    }

    public function deleteMany(iterable $customers): void
    {
        DB::transaction(function () use ($customers): void {
            foreach ($customers as $customer) {
                $this->delete($customer);
            }
        });
    }
}
