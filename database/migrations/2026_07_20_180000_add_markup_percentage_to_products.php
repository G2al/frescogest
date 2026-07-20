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
            $table->decimal('markup_percentage', 8, 2)->default(100)->after('purchase_cost_per_unit');
        });

        DB::table('products')
            ->select(['id', 'purchase_cost_per_unit', 'base_price_per_unit'])
            ->orderBy('id')
            ->eachById(function (object $product): void {
                $cost = (float) $product->purchase_cost_per_unit;
                $price = (float) $product->base_price_per_unit;
                $markup = $cost > 0 ? max(0, (($price - $cost) / $cost) * 100) : 100;

                DB::table('products')
                    ->where('id', $product->id)
                    ->update(['markup_percentage' => round($markup, 2)]);
            });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn('markup_percentage');
        });
    }
};
