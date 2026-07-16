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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('company_name')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('vat_number')->nullable();
            $table->string('tax_code')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('billing_address')->nullable();
            $table->string('delivery_address')->nullable();
            $table->string('city')->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->string('province', 2)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
