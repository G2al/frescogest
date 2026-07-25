<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->replaceForeignKey('delivery_documents', 'order_id', 'orders', 'cascade');
        $this->replaceForeignKey('orders', 'customer_id', 'customers', 'cascade');
        $this->replaceForeignKey('partner_goods_entries', 'product_id', 'products', 'cascade');
        $this->replaceForeignKey('products', 'product_category_id', 'product_categories', 'cascade');

        $customerUserIds = DB::table('customers')
            ->whereNotNull('deleted_at')
            ->whereNotNull('user_id')
            ->pluck('user_id');

        DB::table('customers')->whereNotNull('deleted_at')->delete();

        if ($customerUserIds->isNotEmpty()) {
            DB::table('users')
                ->whereIn('id', $customerUserIds)
                ->where('can_access_panel', false)
                ->whereNotExists(fn ($query) => $query
                    ->selectRaw('1')
                    ->from('partners')
                    ->whereColumn('partners.user_id', 'users.id'))
                ->delete();
        }

        DB::table('products')->whereNotNull('deleted_at')->delete();
        DB::table('product_categories')->whereNotNull('deleted_at')->delete();

        Schema::table('customers', fn (Blueprint $table) => $table->dropSoftDeletes());
        Schema::table('products', fn (Blueprint $table) => $table->dropSoftDeletes());
        Schema::table('product_categories', fn (Blueprint $table) => $table->dropSoftDeletes());
    }

    public function down(): void
    {
        Schema::table('customers', fn (Blueprint $table) => $table->softDeletes());
        Schema::table('products', fn (Blueprint $table) => $table->softDeletes());
        Schema::table('product_categories', fn (Blueprint $table) => $table->softDeletes());

        $this->replaceForeignKey('products', 'product_category_id', 'product_categories', 'restrict');
        $this->replaceForeignKey('partner_goods_entries', 'product_id', 'products', 'restrict');
        $this->replaceForeignKey('orders', 'customer_id', 'customers', 'restrict');
        $this->replaceForeignKey('delivery_documents', 'order_id', 'orders', 'restrict');
    }

    private function replaceForeignKey(
        string $tableName,
        string $column,
        string $references,
        string $onDelete,
    ): void {
        Schema::table($tableName, function (Blueprint $table) use ($column, $references, $onDelete): void {
            $table->dropForeign([$column]);
            $table->foreign($column)
                ->references('id')
                ->on($references)
                ->onDelete($onDelete);
        });
    }
};
