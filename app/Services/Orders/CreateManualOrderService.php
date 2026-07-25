<?php

namespace App\Services\Orders;

use App\Enums\OrderStatus;
use App\Models\Customer;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class CreateManualOrderService
{
    public function __construct(private readonly OrderItemSnapshotService $snapshots) {}

    public function create(array $data): Order
    {
        return DB::transaction(function () use ($data): Order {
            $customer = Customer::query()->findOrFail($data['customer_id']);
            $status = OrderStatus::from($data['status'] ?? OrderStatus::Confirmed->value);
            $order = Order::query()->create([
                'customer_id' => $customer->getKey(),
                'status' => $status,
                'requested_at' => $data['requested_at'] ?? now(),
                'customer_notes' => $data['customer_notes'] ?? null,
                'internal_notes' => $data['internal_notes'] ?? null,
                'delivery_address' => $data['delivery_address'] ?? $customer->delivery_address,
                'delivery_city' => $data['delivery_city'] ?? $customer->city,
                'delivery_postal_code' => $data['delivery_postal_code'] ?? $customer->postal_code,
                'delivery_province' => $data['delivery_province'] ?? $customer->province,
                'delivery_notes' => $data['delivery_notes'] ?? null,
                'expected_delivery_at' => $data['expected_delivery_at'] ?? null,
                'confirmed_at' => $status === OrderStatus::Confirmed ? now() : null,
            ]);

            $order->update([
                'order_number' => 'IPF-'.str_pad((string) $order->getKey(), 6, '0', STR_PAD_LEFT),
            ]);

            foreach (array_values($data['items']) as $index => $item) {
                $order->items()->create($this->snapshots->enrichManual([
                    ...$item,
                    'sort_order' => $index,
                ], $order));
            }

            $this->snapshots->recalculate($order);

            return $order->refresh()->load('items');
        });
    }
}
