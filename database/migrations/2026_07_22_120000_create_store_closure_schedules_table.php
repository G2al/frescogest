<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_closure_schedules', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('type', 30)->index();
            $table->json('weekdays')->nullable();
            $table->date('closure_date')->nullable()->index();
            $table->time('starts_at');
            $table->time('ends_at');
            $table->string('message')->nullable();
            $table->boolean('active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_closure_schedules');
    }
};
