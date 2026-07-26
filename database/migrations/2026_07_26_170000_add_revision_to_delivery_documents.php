<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delivery_documents', function (Blueprint $table): void {
            $table->unsignedInteger('revision')->default(1)->after('document_number');
            $table->timestamp('regenerated_at')->nullable()->after('issued_at');
        });
    }

    public function down(): void
    {
        Schema::table('delivery_documents', function (Blueprint $table): void {
            $table->dropColumn(['revision', 'regenerated_at']);
        });
    }
};
