<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('orders')
            ->select(['id'])
            ->orderBy('id')
            ->eachById(function (object $order): void {
                DB::table('orders')
                    ->where('id', $order->id)
                    ->update(['order_number' => 'IPF-'.str_pad((string) $order->id, 6, '0', STR_PAD_LEFT)]);
            });

        DB::table('products')
            ->select(['id', 'code'])
            ->whereNotNull('code')
            ->orderBy('id')
            ->eachById(function (object $product): void {
                if (! preg_match('/^[A-Z]{2}-[A-F0-9]{8}$/', $product->code)) {
                    return;
                }

                DB::table('products')
                    ->where('id', $product->id)
                    ->update(['code' => 'IPF-'.substr($product->code, 3)]);
            });
    }

    public function down(): void {}
};
