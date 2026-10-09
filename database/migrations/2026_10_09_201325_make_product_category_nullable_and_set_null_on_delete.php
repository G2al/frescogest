<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Eliminando una categoria, i prodotti collegati non devono più essere eliminati
     * a cascata: restano nel catalogo ma senza categoria ("da ricategorizzare"),
     * così l'amministratore può riassegnarli invece di perderli per errore.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['product_category_id']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->unsignedBigInteger('product_category_id')->nullable()->change();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreign('product_category_id')
                ->references('id')
                ->on('product_categories')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['product_category_id']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->unsignedBigInteger('product_category_id')->nullable(false)->change();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreign('product_category_id')
                ->references('id')
                ->on('product_categories')
                ->cascadeOnDelete();
        });
    }
};
