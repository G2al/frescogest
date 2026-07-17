<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->decimal('global_discount_percentage', 5, 2)
                ->nullable()
                ->after('active');
        });

        Schema::create('customer_category_discounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_category_id')->constrained()->cascadeOnDelete();
            $table->decimal('discount_percentage', 5, 2);
            $table->timestamps();
            $table->unique(
                ['customer_id', 'product_category_id'],
                'customer_category_discount_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_category_discounts');

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('global_discount_percentage');
        });
    }
};
