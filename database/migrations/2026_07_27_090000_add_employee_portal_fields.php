<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table): void {
            $table->foreignId('user_id')->nullable()->unique()->after('id')->constrained()->nullOnDelete();
            $table->string('photo_path')->nullable()->after('phone');
        });

        Schema::table('employee_work_shifts', function (Blueprint $table): void {
            $table->string('status', 20)->default('present')->after('work_date')->index();
            $table->time('started_at')->nullable()->change();
            $table->time('ended_at')->nullable()->change();
            $table->string('compensation_type', 20)->nullable()->after('worked_minutes');
            $table->decimal('compensation_rate', 12, 2)->nullable()->after('compensation_type');
            $table->decimal('pay_amount', 12, 2)->default(0)->after('compensation_rate');
        });
    }

    public function down(): void
    {
        Schema::table('employee_work_shifts', function (Blueprint $table): void {
            $table->dropIndex(['status']);
            $table->dropColumn(['status', 'compensation_type', 'compensation_rate', 'pay_amount']);
        });

        Schema::table('employees', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('user_id');
            $table->dropColumn('photo_path');
        });
    }
};
