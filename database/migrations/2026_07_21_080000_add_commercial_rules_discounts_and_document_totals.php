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
            $table->decimal('restaurant_markup_percentage', 8, 2)->default(100)->after('markup_percentage');
        });

        DB::table('products')->where('purchase_cost_per_unit', '>', 0)->update([
            'restaurant_markup_percentage' => DB::raw('CASE WHEN restaurant_price_per_unit > purchase_cost_per_unit THEN ((restaurant_price_per_unit / purchase_cost_per_unit) - 1) * 100 ELSE 0 END'),
        ]);

        Schema::create('commercial_rules', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('customer_type', 20)->index();
            $table->string('province', 2)->nullable()->index();
            $table->string('postal_code_pattern', 20)->nullable();
            $table->decimal('minimum_order_gross', 12, 2)->default(0);
            $table->decimal('free_shipping_threshold_gross', 12, 2)->nullable();
            $table->decimal('shipping_fee_net', 12, 2)->default(0);
            $table->foreignId('shipping_tax_rate_id')->nullable()->constrained('tax_rates')->nullOnDelete();
            $table->unsignedInteger('priority')->default(0);
            $table->boolean('active')->default(true)->index();
            $table->timestamps();
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->decimal('subtotal_net', 14, 2)->default(0)->after('total_amount');
            $table->decimal('discount_percentage', 7, 2)->default(0)->after('subtotal_net');
            $table->decimal('discount_amount_net', 14, 2)->default(0)->after('discount_percentage');
            $table->decimal('shipping_amount_net', 14, 2)->default(0)->after('discount_amount_net');
            $table->decimal('shipping_tax_percentage', 5, 2)->default(0)->after('shipping_amount_net');
            $table->decimal('shipping_tax', 14, 2)->default(0)->after('shipping_tax_percentage');
        });

        DB::table('orders')->update(['subtotal_net' => DB::raw('total_net')]);

        Schema::table('order_items', function (Blueprint $table): void {
            $table->decimal('original_line_net', 14, 2)->default(0)->after('line_total');
            $table->decimal('discount_percentage', 7, 2)->default(0)->after('original_line_net');
            $table->decimal('discount_amount_net', 14, 2)->default(0)->after('discount_percentage');
        });

        DB::table('order_items')->update(['original_line_net' => DB::raw('line_net')]);

        Schema::table('delivery_documents', function (Blueprint $table): void {
            $table->decimal('subtotal_net', 14, 2)->default(0)->after('items_snapshot');
            $table->decimal('discount_percentage', 7, 2)->default(0)->after('subtotal_net');
            $table->decimal('discount_amount_net', 14, 2)->default(0)->after('discount_percentage');
            $table->decimal('shipping_amount_net', 14, 2)->default(0)->after('discount_amount_net');
            $table->string('payment_method_snapshot')->nullable()->after('shipping_amount_net');
        });

        DB::table('delivery_documents')->update(['subtotal_net' => DB::raw('total_net')]);
    }

    public function down(): void
    {
        Schema::table('delivery_documents', fn (Blueprint $table) => $table->dropColumn(['subtotal_net', 'discount_percentage', 'discount_amount_net', 'shipping_amount_net', 'payment_method_snapshot']));
        Schema::table('order_items', fn (Blueprint $table) => $table->dropColumn(['original_line_net', 'discount_percentage', 'discount_amount_net']));
        Schema::table('orders', fn (Blueprint $table) => $table->dropColumn(['subtotal_net', 'discount_percentage', 'discount_amount_net', 'shipping_amount_net', 'shipping_tax_percentage', 'shipping_tax']));
        Schema::dropIfExists('commercial_rules');
        Schema::table('products', fn (Blueprint $table) => $table->dropColumn('restaurant_markup_percentage'));
    }
};
