<?php

namespace App\Services\Promotions;

use App\Enums\PromotionAudience;
use App\Enums\PromotionRule;
use App\Models\Customer;
use App\Models\Order;
use App\Models\PromotionCode;
use App\Models\PromotionCodeUsage;
use Illuminate\Validation\ValidationException;

class PromotionCodeService
{
    public function stickerPromotion(): ?PromotionCode
    {
        return PromotionCode::query()
            ->where('featured_on_sticker', true)
            ->where('active', true)
            ->where(function ($query): void {
                $query->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', now());
            })
            ->where(function ($query): void {
                $query->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', now());
            })
            ->latest('updated_at')
            ->first();
    }

    public function validate(Customer $customer, string $code, bool $lock = false): PromotionCode
    {
        $query = PromotionCode::query()
            ->where('code', $this->normalize($code));

        if ($lock) {
            $query->lockForUpdate();
        }

        $promotion = $query->first();

        if (! $promotion || ! $promotion->active) {
            $this->invalid('Il codice sconto non è valido o non è attivo.');
        }

        if ($promotion->starts_at?->isFuture()) {
            $this->invalid('Questo codice sconto non è ancora attivo.');
        }

        if ($promotion->ends_at?->isPast()) {
            $this->invalid('Questo codice sconto è scaduto.');
        }

        if (! $this->matchesAudience($promotion, $customer)) {
            $this->invalid('Questo codice sconto non è disponibile per la tua tipologia di cliente.');
        }

        if (
            $promotion->rule === PromotionRule::FirstOrder
            && $customer->orders()->exists()
        ) {
            $this->invalid('Questo codice sconto è riservato al primo ordine.');
        }

        if (
            $promotion->single_use_per_customer
            && $promotion->usages()->where('customer_id', $customer->id)->exists()
        ) {
            $this->invalid('Hai già utilizzato questo codice sconto.');
        }

        return $promotion;
    }

    public function recordUsage(PromotionCode $promotion, Customer $customer, Order $order): PromotionCodeUsage
    {
        return PromotionCodeUsage::query()->create([
            'promotion_code_id' => $promotion->id,
            'customer_id' => $customer->id,
            'order_id' => $order->id,
            'used_at' => now(),
        ]);
    }

    private function matchesAudience(PromotionCode $promotion, Customer $customer): bool
    {
        return $promotion->audience === PromotionAudience::Everyone
            || $promotion->audience->value === $customer->type->value;
    }

    private function normalize(string $code): string
    {
        return strtoupper(preg_replace('/\s+/', '', trim($code)));
    }

    private function invalid(string $message): never
    {
        throw ValidationException::withMessages([
            'promotion_code' => $message,
        ]);
    }
}
