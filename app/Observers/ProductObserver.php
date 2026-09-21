<?php

namespace App\Observers;

use App\Models\Product;
use App\Services\Orders\OrderCostRefreshService;
use App\Services\Partners\PartnerPriceListService;
use App\Services\Pricing\CustomerPriceListService;
use Filament\Notifications\Notification;

class ProductObserver
{
    public function created(Product $product): void
    {
        app(CustomerPriceListService::class)->syncProduct($product);
        app(PartnerPriceListService::class)->syncProduct($product);
    }

    public function updated(Product $product): void
    {
        if ($product->wasChanged([
            'purchase_cost_per_unit',
            'partner_markup_percentage',
            'partner_price_per_unit',
            'product_category_id',
            'active',
        ])) {
            app(PartnerPriceListService::class)->syncProduct($product);
        }

        // Il costo di acquisto è cambiato: le bolle emesse oggi con questo prodotto
        // vengono riallineate al costo nuovo, così il guadagno e l'analisi economica
        // non restano calcolati sul costo del giorno prima.
        if ($product->wasChanged(['purchase_cost_per_unit', 'purchase_cost_per_unit_gross', 'tax_rate_id'])) {
            $this->refreshTodaysOrderCosts($product);
        }
    }

    private function refreshTodaysOrderCosts(Product $product): void
    {
        $lines = app(OrderCostRefreshService::class)->refreshProduct($product);

        if ($lines === 0 || app()->runningInConsole()) {
            return;
        }

        Notification::make()
            ->success()
            ->title('Guadagno delle bolle di oggi riallineato')
            ->body($lines === 1
                ? 'Il nuovo costo è stato applicato a 1 riga di una bolla di oggi.'
                : "Il nuovo costo è stato applicato a {$lines} righe delle bolle di oggi.")
            ->send();
    }
}
