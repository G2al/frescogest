<?php

namespace App\Services\Orders;

use App\Models\Order;
use Illuminate\Support\Facades\DB;

class DeleteOrderService
{
    public function delete(Order $order): void
    {
        DB::transaction(function () use ($order): void {
            $order->deliveryDocument()->delete();
            $order->delete();
        });
    }

    public function deleteMany(iterable $orders): void
    {
        DB::transaction(function () use ($orders): void {
            foreach ($orders as $order) {
                $order->deliveryDocument()->delete();
                $order->delete();
            }
        });
    }
}
