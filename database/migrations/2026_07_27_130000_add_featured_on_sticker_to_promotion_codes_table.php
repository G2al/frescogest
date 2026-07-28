<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('promotion_codes', function (Blueprint $table): void {
            $table->boolean('featured_on_sticker')
                ->default(false)
                ->index()
                ->after('active');
        });

        $promotionId = DB::table('promotion_codes')
            ->where('code', 'PARADISO10')
            ->value('id')
            ?? DB::table('promotion_codes')
                ->where('active', true)
                ->orderBy('id')
                ->value('id');

        if ($promotionId) {
            DB::table('promotion_codes')
                ->where('id', $promotionId)
                ->update(['featured_on_sticker' => true]);
        }
    }

    public function down(): void
    {
        Schema::table('promotion_codes', function (Blueprint $table): void {
            $table->dropColumn('featured_on_sticker');
        });
    }
};
