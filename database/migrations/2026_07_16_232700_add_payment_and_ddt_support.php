<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dateTime('paid_at')->nullable()->after('delivered_at')->index();
            $table->string('payment_reference')->nullable()->after('paid_at');
        });

        Schema::create('document_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('document_type', 30);
            $table->unsignedSmallInteger('year');
            $table->unsignedBigInteger('last_number')->default(0);
            $table->timestamps();
            $table->unique(['document_type', 'year']);
        });

        Schema::create('delivery_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('document_number')->unique();
            $table->dateTime('issued_at')->index();
            $table->string('transport_reason');
            $table->string('transport_method');
            $table->string('goods_appearance')->nullable();
            $table->unsignedInteger('packages_count')->nullable();
            $table->decimal('total_weight', 12, 3)->nullable();
            $table->dateTime('transport_started_at')->nullable();
            $table->string('carrier_name')->nullable();
            $table->string('carrier_vat_number', 32)->nullable();
            $table->string('carrier_tax_code', 32)->nullable();
            $table->string('vehicle_registration', 20)->nullable();
            $table->text('notes')->nullable();
            $table->json('sender_snapshot');
            $table->json('recipient_snapshot');
            $table->json('destination_snapshot');
            $table->json('items_snapshot');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_documents');
        Schema::dropIfExists('document_sequences');

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['paid_at']);
            $table->dropColumn(['paid_at', 'payment_reference']);
        });
    }
};
