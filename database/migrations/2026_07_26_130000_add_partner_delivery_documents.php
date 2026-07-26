<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delivery_documents', function (Blueprint $table): void {
            $table->dropForeign(['order_id']);
            $table->unsignedBigInteger('order_id')->nullable()->change();
            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
            $table->foreignId('partner_id')->nullable()->after('order_id')->constrained()->cascadeOnDelete();
        });

        Schema::table('partner_goods_entries', function (Blueprint $table): void {
            $table->foreignId('delivery_document_id')
                ->nullable()
                ->after('partner_id')
                ->constrained()
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        DB::table('delivery_documents')->whereNotNull('partner_id')->delete();

        Schema::table('partner_goods_entries', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('delivery_document_id');
        });

        Schema::table('delivery_documents', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('partner_id');
            $table->dropForeign(['order_id']);
            $table->unsignedBigInteger('order_id')->nullable(false)->change();
            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
        });
    }
};
