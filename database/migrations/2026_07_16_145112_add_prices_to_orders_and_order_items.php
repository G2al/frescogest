<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('total_amount', 12, 2)->default(0)->after('customer_notes');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->decimal('price_per_kg', 10, 2)->default(0)->after('quantity');
            $table->decimal('line_total', 12, 2)->default(0)->after('price_per_kg');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['price_per_kg', 'line_total']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('total_amount');
        });
    }
};
