<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table): void {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('compensation_type', 20);
            $table->decimal('compensation_amount', 12, 2);
            $table->unsignedSmallInteger('expected_daily_minutes');
            $table->date('hired_on');
            $table->date('terminated_on')->nullable();
            $table->boolean('active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['active', 'hired_on']);
            $table->index('terminated_on');
        });

        Schema::create('employee_work_shifts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('work_date');
            $table->time('started_at');
            $table->time('ended_at');
            $table->unsignedSmallInteger('break_minutes')->default(0);
            $table->unsignedSmallInteger('expected_minutes');
            $table->unsignedSmallInteger('worked_minutes');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'work_date']);
            $table->index('work_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_work_shifts');
        Schema::dropIfExists('employees');
    }
};
