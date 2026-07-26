<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('partner_goods_entries', function (Blueprint $table): void {
            $table->decimal('unit_cost_net', 12, 4)->default(0)->after('unit_purchase_price_net');
            $table->decimal('total_cost_net', 14, 2)->default(0)->after('total_gross');
            $table->decimal('total_cost_tax', 14, 2)->default(0)->after('total_cost_net');
            $table->decimal('total_cost_gross', 14, 2)->default(0)->after('total_cost_tax');
        });

        DB::table('partner_goods_entries')
            ->orderBy('id')
            ->chunkById(200, function ($entries): void {
                $products = DB::table('products')
                    ->whereIn('id', $entries->pluck('product_id')->unique())
                    ->pluck('purchase_cost_per_unit', 'id');

                foreach ($entries as $entry) {
                    $unitCost = (float) ($products[$entry->product_id] ?? 0);
                    $totalCostNet = round($unitCost * (float) $entry->quantity, 2);
                    $totalCostTax = round($totalCostNet * ((float) $entry->tax_percentage / 100), 2);

                    DB::table('partner_goods_entries')
                        ->where('id', $entry->id)
                        ->update([
                            'unit_cost_net' => $unitCost,
                            'total_cost_net' => $totalCostNet,
                            'total_cost_tax' => $totalCostTax,
                            'total_cost_gross' => round($totalCostNet + $totalCostTax, 2),
                        ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('partner_goods_entries', function (Blueprint $table): void {
            $table->dropColumn([
                'unit_cost_net',
                'total_cost_net',
                'total_cost_tax',
                'total_cost_gross',
            ]);
        });
    }
};
