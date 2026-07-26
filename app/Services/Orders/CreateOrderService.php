<?php

namespace App\Services\Orders;

use App\Enums\OrderStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\Promotions\PromotionCodeService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateOrderService
{
    public function __construct(
        private readonly OrderItemSnapshotService $snapshots,
        private readonly CommercialRuleService $commercialRules,
        private readonly PromotionCodeService $promotions,
    ) {}

    public function create(User $user, array $data): Order
    {
        $customer = $user->customer;
        $requestedItems = collect($data['items'])->keyBy('product_id');
        $products = Product::query()->publicCatalog()->whereKey($requestedItems->keys())->pluck('id');

        if ($products->count() !== $requestedItems->count()) {
            throw ValidationException::withMessages(['items' => 'Uno o più prodotti non sono disponibili nel catalogo.']);
        }

        return DB::transaction(function () use ($customer, $data, $requestedItems): Order {
            $customer = Customer::query()->lockForUpdate()->findOrFail($customer->id);
            $promotion = filled($data['promotion_code'] ?? null)
                ? $this->promotions->validate($customer, $data['promotion_code'], true)
                : null;

            $order = Order::query()->create([
                'customer_id' => $customer->id,
                'promotion_code_id' => $promotion?->id,
                'promotion_code_snapshot' => $promotion?->code,
                'promotion_discount_percentage' => $promotion?->discount_percentage ?? 0,
                'discount_percentage' => $promotion?->discount_percentage ?? 0,
                'status' => OrderStatus::WhatsAppPending,
                'requested_at' => now(),
                'customer_notes' => $data['customer_notes'] ?? null,
                'delivery_address' => $customer->delivery_address ?: $customer->billing_address,
                'delivery_city' => $customer->city,
                'delivery_postal_code' => $customer->postal_code,
                'delivery_province' => $customer->province,
            ]);
            $order->update(['order_number' => 'IPF-'.str_pad((string) $order->id, 6, '0', STR_PAD_LEFT)]);

            foreach ($requestedItems->values() as $index => $item) {
                $order->items()->create($this->snapshots->enrich([
                    ...$item,
                    'sort_order' => $index,
                ], $order));
            }

            $this->commercialRules->apply($order);
            $this->snapshots->recalculate($order);

            if ($promotion) {
                $this->promotions->recordUsage($promotion, $customer, $order);
            }

            return $order->load(['customer', 'items.product', 'promotionCode']);
        });
    }
}
