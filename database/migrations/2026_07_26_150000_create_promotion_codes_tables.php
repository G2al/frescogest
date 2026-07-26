<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotion_codes', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('code', 64)->unique();
            $table->decimal('discount_percentage', 7, 2);
            $table->string('audience', 20)->index();
            $table->string('rule', 30)->default('any');
            $table->timestamp('starts_at')->nullable()->index();
            $table->timestamp('ends_at')->nullable()->index();
            $table->boolean('single_use_per_customer')->default(true);
            $table->boolean('active')->default(true)->index();
            $table->timestamps();
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->foreignId('promotion_code_id')
                ->nullable()
                ->after('customer_id')
                ->constrained()
                ->nullOnDelete();
            $table->string('promotion_code_snapshot', 64)->nullable()->after('promotion_code_id');
            $table->decimal('promotion_discount_percentage', 7, 2)
                ->default(0)
                ->after('promotion_code_snapshot');
        });

        Schema::create('promotion_code_usages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('promotion_code_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->unique()->constrained()->cascadeOnDelete();
            $table->timestamp('used_at');
            $table->timestamps();
            $table->index(['promotion_code_id', 'customer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_code_usages');

        Schema::table('orders', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('promotion_code_id');
            $table->dropColumn([
                'promotion_code_snapshot',
                'promotion_discount_percentage',
            ]);
        });

        Schema::dropIfExists('promotion_codes');
    }
};
