<?php

namespace App\Services\Orders;

use App\Models\CommercialRule;
use App\Models\Customer;
use App\Models\Order;
use App\Services\Pricing\PriceCalculator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CommercialRuleService
{
    public function __construct(private readonly PriceCalculator $calculator) {}

    public function apply(Order $order): void
    {
        $customer = $order->customer;
        $rule = $this->findFor($customer);

        if (! $rule) {
            return;
        }

        $productsGross = (float) $order->items()->sum('line_gross');

        if ($productsGross < (float) $rule->minimum_order_gross) {
            throw ValidationException::withMessages([
                'items' => 'Per completare l’ordine è richiesta una spesa minima di '.number_format((float) $rule->minimum_order_gross, 2, ',', '.').' € IVA inclusa.',
            ]);
        }

        $freeThreshold = $rule->free_shipping_threshold_gross;
        $shippingNet = filled($freeThreshold) && $productsGross >= (float) $freeThreshold
            ? 0
            : (float) $rule->shipping_fee_net;
        $shippingTaxPercentage = (float) ($rule->shippingTaxRate?->percentage ?? 0);

        $order->update([
            'shipping_amount_net' => $shippingNet,
            'shipping_tax_percentage' => $shippingTaxPercentage,
            'shipping_tax' => $this->calculator->tax($shippingNet, $shippingTaxPercentage),
        ]);
    }

    public function findFor(Customer $customer): ?CommercialRule
    {
        return CommercialRule::query()
            ->with('shippingTaxRate')
            ->where('active', true)
            ->where('customer_type', $customer->type->value)
            ->where(function ($query) use ($customer): void {
                $query->whereNull('province')->orWhere('province', strtoupper((string) $customer->province));
            })
            ->orderByRaw('CASE WHEN province IS NULL THEN 1 ELSE 0 END')
            ->orderByRaw('CASE WHEN postal_code_pattern IS NULL THEN 1 ELSE 0 END')
            ->orderByDesc('priority')
            ->get()
            ->first(function (CommercialRule $rule) use ($customer): bool {
                if (blank($rule->postal_code_pattern)) {
                    return true;
                }

                return Str::is($rule->postal_code_pattern, (string) $customer->postal_code);
            });
    }
}
