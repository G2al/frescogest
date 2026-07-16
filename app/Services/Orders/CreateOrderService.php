<?php

namespace App\Services\Orders;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\Pricing\PriceCalculator;
use App\Services\Pricing\ProductPricingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateOrderService
{
    public function __construct(
        private readonly ProductPricingService $pricing,
        private readonly PriceCalculator $calculator,
    ) {}

    public function create(User $user, array $data): Order
    {
        $customer = $user->customer;
        $requestedItems = collect($data['items'])->keyBy('product_id');

        $products = Product::query()
            ->publicCatalog()
            ->with(['productCategory', 'defaultUnitOfMeasure'])
            ->whereKey($requestedItems->keys())
            ->get()
            ->keyBy('id');

        if ($products->count() !== $requestedItems->count()) {
            throw ValidationException::withMessages([
                'items' => 'Uno o più prodotti non sono disponibili nel catalogo.',
            ]);
        }

        return DB::transaction(function () use ($customer, $data, $requestedItems, $products): Order {
            $order = Order::query()->create([
                'customer_id' => $customer->id,
                'status' => OrderStatus::PendingContact,
                'requested_at' => now(),
                'customer_notes' => $data['customer_notes'] ?? null,
            ]);

            $order->update([
                'order_number' => 'FG-'.str_pad((string) $order->id, 6, '0', STR_PAD_LEFT),
            ]);

            $lineTotals = [];

            foreach ($requestedItems->values() as $index => $item) {
                $product = $products->get($item['product_id']);
                $pricePerKg = $this->pricing->resolve($product, $customer);
                $lineTotal = $this->calculator->lineTotal($pricePerKg, $item['quantity']);
                $lineTotals[] = $lineTotal;

                $order->items()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => $item['quantity'],
                    'price_per_kg' => $pricePerKg,
                    'line_total' => $lineTotal,
                    'unit_of_measure_name' => 'Chilogrammi',
                    'unit_of_measure_symbol' => 'kg',
                    'sort_order' => $index,
                ]);
            }

            $order->update(['total_amount' => $this->calculator->sum($lineTotals)]);

            return $order->load(['customer', 'items']);
        });
    }
}
