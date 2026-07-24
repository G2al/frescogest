<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('panel_role', 20)->nullable()->after('can_access_panel')->index();
        });

        DB::table('users')
            ->where('can_access_panel', true)
            ->update(['panel_role' => 'admin']);

        Schema::create('partners', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('partner_product_prices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('partner_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->decimal('purchase_price_net', 12, 4);
            $table->decimal('sale_price_net', 12, 4);
            $table->decimal('markup_percentage', 8, 2)->default(100);
            $table->timestamps();
            $table->unique(['partner_id', 'product_id']);
        });

        Schema::create('partner_goods_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('partner_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->date('delivered_on')->index();
            $table->decimal('quantity', 12, 3);
            $table->decimal('unit_purchase_price_net', 12, 4);
            $table->decimal('tax_percentage', 5, 2);
            $table->decimal('total_net', 14, 2);
            $table->decimal('total_tax', 14, 2);
            $table->decimal('total_gross', 14, 2);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['partner_id', 'delivered_on']);
        });

        Schema::create('partner_daily_receipts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('partner_id')->constrained()->cascadeOnDelete();
            $table->date('receipt_date')->index();
            $table->decimal('gross_amount', 14, 2);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['partner_id', 'receipt_date']);
        });

        Schema::create('partner_daily_wastes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('partner_id')->constrained()->cascadeOnDelete();
            $table->date('waste_date')->index();
            $table->decimal('amount', 14, 2);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['partner_id', 'waste_date']);
        });

        Schema::create('partner_expenses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('partner_id')->constrained()->cascadeOnDelete();
            $table->date('expense_date')->index();
            $table->string('description');
            $table->decimal('amount', 14, 2);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['partner_id', 'expense_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_expenses');
        Schema::dropIfExists('partner_daily_wastes');
        Schema::dropIfExists('partner_daily_receipts');
        Schema::dropIfExists('partner_goods_entries');
        Schema::dropIfExists('partner_product_prices');
        Schema::dropIfExists('partners');

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('panel_role');
        });
    }
};
