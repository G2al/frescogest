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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->nullable()->unique();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('payment_method_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->index();
            $table->dateTime('requested_at');
            $table->text('customer_notes')->nullable();
            $table->text('internal_notes')->nullable();
            $table->string('delivery_address')->nullable();
            $table->string('delivery_city')->nullable();
            $table->string('delivery_postal_code', 10)->nullable();
            $table->string('delivery_province', 2)->nullable();
            $table->text('delivery_notes')->nullable();
            $table->dateTime('confirmed_at')->nullable();
            $table->dateTime('expected_delivery_at')->nullable();
            $table->dateTime('delivered_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
