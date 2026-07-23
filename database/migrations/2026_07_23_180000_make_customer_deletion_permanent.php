<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('customers', 'deleted_at')) {
            DB::transaction(function (): void {
                $archivedCustomers = DB::table('customers')
                    ->whereNotNull('deleted_at')
                    ->get(['id', 'user_id']);

                $customerIds = $archivedCustomers->pluck('id');
                $userIds = $archivedCustomers->pluck('user_id')->filter();
                $users = DB::table('users')->whereIn('id', $userIds)->get(['id', 'email']);
                $orderIds = DB::table('orders')->whereIn('customer_id', $customerIds)->pluck('id');

                DB::table('delivery_documents')->whereIn('order_id', $orderIds)->delete();
                DB::table('orders')->whereIn('id', $orderIds)->delete();
                DB::table('customers')->whereIn('id', $customerIds)->delete();
                DB::table('sessions')->whereIn('user_id', $users->pluck('id'))->delete();
                DB::table('password_reset_tokens')->whereIn('email', $users->pluck('email'))->delete();
                DB::table('users')->whereIn('id', $users->pluck('id'))->delete();
            });
        }

        DB::transaction(function (): void {
            $orphanUsers = DB::table('users')
                ->where('can_access_panel', false)
                ->whereNotExists(function ($query): void {
                    $query
                        ->selectRaw('1')
                        ->from('customers')
                        ->whereColumn('customers.user_id', 'users.id');
                })
                ->get(['id', 'email']);

            DB::table('sessions')->whereIn('user_id', $orphanUsers->pluck('id'))->delete();
            DB::table('password_reset_tokens')->whereIn('email', $orphanUsers->pluck('email'))->delete();
            DB::table('users')->whereIn('id', $orphanUsers->pluck('id'))->delete();
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->dropForeign(['customer_id']);
            $table->foreign('customer_id')->references('id')->on('customers')->cascadeOnDelete();
        });

        Schema::table('delivery_documents', function (Blueprint $table): void {
            $table->dropForeign(['order_id']);
            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
        });

        if (Schema::hasColumn('customers', 'deleted_at')) {
            Schema::table('customers', function (Blueprint $table): void {
                $table->dropSoftDeletes();
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('customers', 'deleted_at')) {
            Schema::table('customers', function (Blueprint $table): void {
                $table->softDeletes();
            });
        }

        Schema::table('delivery_documents', function (Blueprint $table): void {
            $table->dropForeign(['order_id']);
            $table->foreign('order_id')->references('id')->on('orders')->restrictOnDelete();
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->dropForeign(['customer_id']);
            $table->foreign('customer_id')->references('id')->on('customers')->restrictOnDelete();
        });
    }
};
