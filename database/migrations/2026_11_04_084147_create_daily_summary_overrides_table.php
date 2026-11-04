<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('daily_summary_overrides', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('name');
            $table->string('symbol')->nullable();
            $table->decimal('quantity', 12, 3)->default(0);
            $table->decimal('net', 12, 2)->default(0);
            $table->decimal('tax', 12, 2)->default(0);
            $table->decimal('gross', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['date', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_summary_overrides');
    }
};
