<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->string('image_path_2')->nullable()->after('image_path');
            $table->string('image_path_3')->nullable()->after('image_path_2');
        });

        Schema::table('product_variants', function (Blueprint $table): void {
            $table->string('sku')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table): void {
            $table->string('sku')->nullable(false)->change();
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn(['image_path_2', 'image_path_3']);
        });
    }
};
