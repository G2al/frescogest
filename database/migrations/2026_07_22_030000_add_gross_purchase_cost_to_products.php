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
            $table->decimal('purchase_cost_per_unit_gross', 12, 4)
                ->default(0)
                ->after('purchase_cost_per_unit');
        });

        $taxRates = DB::table('tax_rates')->pluck('percentage', 'id');

        DB::table('products')
            ->select(['id', 'tax_rate_id', 'purchase_cost_per_unit'])
            ->orderBy('id')
            ->eachById(function (object $product) use ($taxRates): void {
                $percentage = (float) ($taxRates->get($product->tax_rate_id) ?? 0);
                $grossCost = (float) $product->purchase_cost_per_unit * (1 + ($percentage / 100));

                DB::table('products')->where('id', $product->id)->update([
                    'purchase_cost_per_unit_gross' => round($grossCost, 4),
                ]);
            });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn('purchase_cost_per_unit_gross');
        });
    }
};
