<?php

namespace App\Services\Orders;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Validation\ValidationException;

class RecordOrderPaymentService
{
    public function record(Order $order, array $data): Order
    {
        if (round((float) $data['payment_amount'], 2) !== round((float) $order->total_gross, 2)) {
            throw ValidationException::withMessages([
                'payment_amount' => 'Il pagamento deve coprire l’intero totale di € '.number_format((float) $order->total_gross, 2, ',', '.').'.',
            ]);
        }

        $order->update([
            'status' => OrderStatus::Paid,
            'paid_at' => $data['paid_at'],
            'payment_amount' => $data['payment_amount'],
            'payment_method_id' => $data['payment_method_id'],
            'payment_reference' => $data['payment_reference'] ?? null,
        ]);

        return $order->refresh();
    }

    public function clear(Order $order): Order
    {
        $order->update([
            'status' => OrderStatus::Confirmed,
            'paid_at' => null,
            'payment_amount' => null,
            'payment_method_id' => null,
            'payment_reference' => null,
        ]);

        return $order->refresh();
    }
}
