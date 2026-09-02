<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('producers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_category_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['product_category_id', 'slug']);
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->foreignId('producer_id')->nullable()->after('product_category_id')
                ->constrained()->nullOnDelete();
        });

        Schema::table('product_categories', function (Blueprint $table): void {
            $table->boolean('sort_alphabetically')->default(false)->after('sort_order');
        });
    }

    public function down(): void
    {
        Schema::table('product_categories', function (Blueprint $table): void {
            $table->dropColumn('sort_alphabetically');
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('producer_id');
        });

        Schema::dropIfExists('producers');
    }
};
