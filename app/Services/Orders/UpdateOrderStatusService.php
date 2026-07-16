<?php

namespace App\Services\Orders;

use App\Enums\OrderStatus;
use App\Models\Order;

class UpdateOrderStatusService
{
    public function update(Order $order, OrderStatus $status): Order
    {
        $data = ['status' => $status];

        if ($status === OrderStatus::Confirmed && $order->confirmed_at === null) {
            $data['confirmed_at'] = now();
        }

        if ($status === OrderStatus::Delivered) {
            $data['delivered_at'] = now();
        } elseif ($order->status === OrderStatus::Delivered) {
            $data['delivered_at'] = null;
        }

        if ($status === OrderStatus::PendingContact) {
            $data['confirmed_at'] = null;
            $data['delivered_at'] = null;
        }

        $order->update($data);

        return $order->refresh();
    }
}
