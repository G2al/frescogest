<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Stessa logica già applicata alle categorie: eliminando un'unità di misura,
     * i prodotti collegati non devono più bloccare la cancellazione. Restano nel
     * catalogo ma senza unità ("da ricategorizzare") finché non li riassegni.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['default_unit_of_measure_id']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->unsignedBigInteger('default_unit_of_measure_id')->nullable()->change();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreign('default_unit_of_measure_id')
                ->references('id')
                ->on('unit_of_measures')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['default_unit_of_measure_id']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->unsignedBigInteger('default_unit_of_measure_id')->nullable(false)->change();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreign('default_unit_of_measure_id')
                ->references('id')
                ->on('unit_of_measures')
                ->restrictOnDelete();
        });
    }
};
