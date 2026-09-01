<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->decimal('partner_markup_percentage', 8, 2)->default(35)->after('restaurant_markup_percentage');
            $table->decimal('partner_price_per_unit', 12, 4)->default(0)->after('restaurant_price_per_unit');
        });

        Schema::table('partner_product_prices', function (Blueprint $table): void {
            $table->boolean('purchase_price_is_custom')->default(false)->after('purchase_price_net');
        });

        Schema::create('special_price_rules', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('audience', 20)->index();
            $table->string('scope_type', 20)->index();
            $table->foreignId('partner_id')->nullable()->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('product_category_id')->nullable()->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->decimal('markup_percentage', 8, 2);
            $table->boolean('active')->default(true)->index();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['audience', 'active', 'product_category_id'], 'special_prices_audience_category_index');
            $table->index(['audience', 'active', 'product_id'], 'special_prices_audience_product_index');
        });

        DB::table('products')
            ->select(['id', 'purchase_cost_per_unit'])
            ->orderBy('id')
            ->chunkById(250, function ($products): void {
                foreach ($products as $product) {
                    DB::table('products')->where('id', $product->id)->update([
                        'partner_price_per_unit' => round((float) $product->purchase_cost_per_unit * 1.35, 4),
                    ]);
                }
            });

        DB::table('partner_product_prices')
            ->select(['id', 'product_id', 'purchase_price_net', 'markup_percentage'])
            ->orderBy('id')
            ->chunkById(250, function ($prices): void {
                $products = DB::table('products')
                    ->whereIn('id', $prices->pluck('product_id'))
                    ->get(['id', 'base_price_per_unit', 'partner_price_per_unit'])
                    ->keyBy('id');

                foreach ($prices as $price) {
                    $product = $products->get($price->product_id);
                    $previousDefault = (float) ($product?->base_price_per_unit ?? 0);
                    $isCustom = abs((float) $price->purchase_price_net - $previousDefault) > 0.0001;
                    $updates = ['purchase_price_is_custom' => $isCustom];

                    if (! $isCustom && $product) {
                        $partnerPrice = (float) $product->partner_price_per_unit;
                        $updates['purchase_price_net'] = $partnerPrice;
                        $updates['sale_price_net'] = round(
                            $partnerPrice * (1 + ((float) $price->markup_percentage / 100)),
                            4,
                        );
                    }

                    DB::table('partner_product_prices')->where('id', $price->id)->update($updates);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('special_price_rules');

        Schema::table('partner_product_prices', function (Blueprint $table): void {
            $table->dropColumn('purchase_price_is_custom');
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn(['partner_markup_percentage', 'partner_price_per_unit']);
        });
    }
};
