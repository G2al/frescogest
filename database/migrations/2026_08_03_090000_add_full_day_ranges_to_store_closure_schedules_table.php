<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_closure_schedules', function (Blueprint $table): void {
            $table->date('closure_end_date')->nullable()->after('closure_date')->index();
            $table->time('starts_at')->nullable()->change();
            $table->time('ends_at')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('store_closure_schedules', function (Blueprint $table): void {
            $table->dropIndex(['closure_end_date']);
            $table->dropColumn('closure_end_date');
            $table->time('starts_at')->nullable(false)->change();
            $table->time('ends_at')->nullable(false)->change();
        });
    }
};
