<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->string('type', 20)->default('private')->after('user_id')->index();
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->decimal('purchase_cost_per_unit', 12, 4)->default(0)->after('price_per_kg');
            $table->decimal('base_price_per_unit', 12, 4)->default(0)->after('purchase_cost_per_unit');
            $table->decimal('restaurant_price_per_unit', 12, 4)->default(0)->after('base_price_per_unit');
            $table->decimal('base_minimum_quantity', 12, 3)->default(1)->after('restaurant_price_per_unit');
            $table->decimal('restaurant_minimum_quantity', 12, 3)->default(5)->after('base_minimum_quantity');
        });

        DB::table('products')->update([
            'base_price_per_unit' => DB::raw('price_per_kg'),
            'restaurant_price_per_unit' => DB::raw('price_per_kg'),
        ]);

        Schema::table('customer_product_prices', function (Blueprint $table): void {
            $table->decimal('custom_price_per_unit', 12, 4)->nullable()->after('custom_price_per_kg');
            $table->decimal('custom_minimum_quantity', 12, 3)->nullable()->after('custom_price_per_unit');
        });

        DB::table('customer_product_prices')->update([
            'custom_price_per_unit' => DB::raw('custom_price_per_kg'),
        ]);

        Schema::table('orders', function (Blueprint $table): void {
            $table->decimal('total_net', 14, 2)->default(0)->after('total_amount');
            $table->decimal('total_tax', 14, 2)->default(0)->after('total_net');
            $table->decimal('total_gross', 14, 2)->default(0)->after('total_tax');
            $table->decimal('total_purchase_cost_net', 14, 2)->default(0)->after('total_gross');
            $table->decimal('gross_margin', 14, 2)->default(0)->after('total_purchase_cost_net');
            $table->decimal('gross_margin_percentage', 7, 2)->default(0)->after('gross_margin');
            $table->decimal('payment_amount', 14, 2)->nullable()->after('paid_at');
        });

        DB::table('orders')->update([
            'total_net' => DB::raw('total_amount'),
            'total_gross' => DB::raw('total_amount'),
        ]);
        DB::table('orders')->where('status', 'pending_contact')->update(['status' => 'whatsapp_pending']);
        DB::table('orders')->whereIn('status', ['preparing', 'delivered'])->update(['status' => 'confirmed']);
        DB::table('orders')->whereNotNull('paid_at')->update(['status' => 'paid']);

        Schema::table('order_items', function (Blueprint $table): void {
            $table->decimal('unit_price_net', 12, 4)->default(0)->after('price_per_kg');
            $table->decimal('tax_percentage', 5, 2)->default(0)->after('unit_price_net');
            $table->decimal('line_net', 14, 2)->default(0)->after('line_total');
            $table->decimal('line_tax', 14, 2)->default(0)->after('line_net');
            $table->decimal('line_gross', 14, 2)->default(0)->after('line_tax');
            $table->decimal('purchase_cost_per_unit_net', 12, 4)->default(0)->after('line_gross');
            $table->decimal('purchase_cost_net', 14, 2)->default(0)->after('purchase_cost_per_unit_net');
            $table->decimal('purchase_cost_tax', 14, 2)->default(0)->after('purchase_cost_net');
            $table->decimal('purchase_cost_gross', 14, 2)->default(0)->after('purchase_cost_tax');
            $table->decimal('margin_amount', 14, 2)->default(0)->after('purchase_cost_gross');
            $table->decimal('margin_percentage', 7, 2)->default(0)->after('margin_amount');
        });

        DB::table('order_items')->update([
            'unit_price_net' => DB::raw('price_per_kg'),
            'line_net' => DB::raw('line_total'),
            'line_gross' => DB::raw('line_total'),
        ]);

        Schema::table('delivery_documents', function (Blueprint $table): void {
            $table->decimal('total_net', 14, 2)->default(0)->after('items_snapshot');
            $table->decimal('total_tax', 14, 2)->default(0)->after('total_net');
            $table->decimal('total_gross', 14, 2)->default(0)->after('total_tax');
        });

        Schema::create('cost_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->boolean('is_monthly')->default(false);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('cost_movements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('cost_category_id')->constrained()->restrictOnDelete();
            $table->date('movement_date')->index();
            $table->decimal('amount', 14, 2);
            $table->string('description');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        DB::table('orders')->where('status', 'whatsapp_pending')->update(['status' => 'pending_contact']);
        DB::table('orders')->where('status', 'paid')->update(['status' => 'delivered']);

        Schema::dropIfExists('cost_movements');
        Schema::dropIfExists('cost_categories');

        Schema::table('delivery_documents', fn (Blueprint $table) => $table->dropColumn(['total_net', 'total_tax', 'total_gross']));
        Schema::table('order_items', fn (Blueprint $table) => $table->dropColumn(['unit_price_net', 'tax_percentage', 'line_net', 'line_tax', 'line_gross', 'purchase_cost_per_unit_net', 'purchase_cost_net', 'purchase_cost_tax', 'purchase_cost_gross', 'margin_amount', 'margin_percentage']));
        Schema::table('orders', fn (Blueprint $table) => $table->dropColumn(['total_net', 'total_tax', 'total_gross', 'total_purchase_cost_net', 'gross_margin', 'gross_margin_percentage', 'payment_amount']));
        Schema::table('customer_product_prices', fn (Blueprint $table) => $table->dropColumn(['custom_price_per_unit', 'custom_minimum_quantity']));
        Schema::table('products', fn (Blueprint $table) => $table->dropColumn(['purchase_cost_per_unit', 'base_price_per_unit', 'restaurant_price_per_unit', 'base_minimum_quantity', 'restaurant_minimum_quantity']));
        Schema::table('customers', fn (Blueprint $table) => $table->dropColumn('type'));
    }
};
