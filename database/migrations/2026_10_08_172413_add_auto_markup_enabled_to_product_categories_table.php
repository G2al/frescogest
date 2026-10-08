<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('product_categories', function (Blueprint $table) {
            $table->boolean('auto_markup_enabled')->default(false)->after('sort_alphabetically');
        });

        // Frutta e Verdura sono le uniche categorie dove, ad oggi, Antonio vuole che il
        // prezzo di vendita si aggiorni da solo quando cambia il costo di acquisto.
        DB::table('product_categories')
            ->where(function ($query) {
                $query->where('name', 'like', '%frutta%')
                    ->orWhere('name', 'like', '%verdura%');
            })
            ->update(['auto_markup_enabled' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_categories', function (Blueprint $table) {
            $table->dropColumn('auto_markup_enabled');
        });
    }
};
