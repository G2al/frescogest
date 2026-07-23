<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->string('brand')->nullable()->after('code');
        });

        Schema::create('product_variants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('sku')->unique();
            $table->string('size', 30)->nullable();
            $table->string('color', 80)->nullable();
            $table->boolean('active')->default(true)->index();
            $table->timestamps();
            $table->unique(['product_id', 'size', 'color']);
        });

        Schema::table('order_items', function (Blueprint $table): void {
            $table->foreignId('product_variant_id')
                ->nullable()
                ->after('product_id')
                ->constrained()
                ->nullOnDelete();
            $table->string('variant_sku')->nullable()->after('product_name');
            $table->string('variant_size', 30)->nullable()->after('variant_sku');
            $table->string('variant_color', 80)->nullable()->after('variant_size');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('product_variant_id');
            $table->dropColumn(['variant_sku', 'variant_size', 'variant_color']);
        });

        Schema::dropIfExists('product_variants');

        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn('brand');
        });
    }
};
