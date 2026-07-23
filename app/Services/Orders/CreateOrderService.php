<?php

namespace App\Services\Orders;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateOrderService
{
    public function __construct(
        private readonly OrderItemSnapshotService $snapshots,
        private readonly CommercialRuleService $commercialRules,
    ) {}

    public function create(User $user, array $data): Order
    {
        $customer = $user->customer;
        $requestedItems = collect($data['items']);
        $productIds = $requestedItems->pluck('product_id')->unique()->values();
        $products = Product::query()->publicCatalog()->whereKey($productIds)->pluck('id');

        if ($products->count() !== $productIds->count()) {
            throw ValidationException::withMessages(['items' => 'Uno o più prodotti non sono disponibili nel catalogo.']);
        }

        return DB::transaction(function () use ($customer, $data, $requestedItems): Order {
            $order = Order::query()->create([
                'customer_id' => $customer->id,
                'status' => OrderStatus::WhatsAppPending,
                'requested_at' => now(),
                'customer_notes' => $data['customer_notes'] ?? null,
                'delivery_address' => $customer->delivery_address ?: $customer->billing_address,
                'delivery_city' => $customer->city,
                'delivery_postal_code' => $customer->postal_code,
                'delivery_province' => $customer->province,
            ]);
            $order->update(['order_number' => 'CS-'.str_pad((string) $order->id, 6, '0', STR_PAD_LEFT)]);

            foreach ($requestedItems as $index => $item) {
                $order->items()->create($this->snapshots->enrich([
                    ...$item,
                    'sort_order' => $index,
                ], $order));
            }

            $this->commercialRules->apply($order);
            $this->snapshots->recalculate($order);

            return $order->load(['customer', 'items.product']);
        });
    }
}
