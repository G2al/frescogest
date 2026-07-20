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

        if ($status === OrderStatus::WhatsAppPending) {
            $data['confirmed_at'] = null;
        }

        if ($status !== OrderStatus::Paid) {
            $data += ['paid_at' => null, 'payment_amount' => null, 'payment_method_id' => null];
        }

        $order->update($data);

        return $order->refresh();
    }
}
