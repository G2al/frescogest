<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('products')->update([
            'restaurant_price_per_unit' => DB::raw('base_price_per_unit'),
        ]);
    }

    public function down(): void {}
};
