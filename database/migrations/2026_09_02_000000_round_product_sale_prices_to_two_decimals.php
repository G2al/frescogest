<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * I tre prezzi di vendita (privati, ristoratori, partner) sono valori
     * monetari finali e devono avere al massimo 2 decimali, come ovunque
     * nel resto dello schema (order_items.line_net, customer_product_prices, ecc.).
     *
     * Restringere la colonna da decimal(12,4) a decimal(12,2) fa sì che MySQL
     * arrotondi automaticamente, in fase di ALTER TABLE, i valori già salvati
     * a 4 decimali: nessun dato viene perso, solo arrotondato.
     *
     * Il costo di acquisto (purchase_cost_per_unit, purchase_cost_per_unit_gross)
     * resta volutamente a 4 decimali: è un valore di calcolo interno derivato
     * dalla conversione IVA e serve una precisione maggiore per i margini.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->decimal('base_price_per_unit', 12, 2)->default(0)->change();
            $table->decimal('restaurant_price_per_unit', 12, 2)->default(0)->change();
            $table->decimal('partner_price_per_unit', 12, 2)->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->decimal('base_price_per_unit', 12, 4)->default(0)->change();
            $table->decimal('restaurant_price_per_unit', 12, 4)->default(0)->change();
            $table->decimal('partner_price_per_unit', 12, 4)->default(0)->change();
        });
    }
};
