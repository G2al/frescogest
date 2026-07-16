<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('product_categories', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('name');
            $table->string('image_path')->nullable()->after('description');
            $table->boolean('is_public')->default(false)->after('image_path');
            $table->unsignedInteger('sort_order')->default(0)->after('is_public');
        });

        DB::table('product_categories')->orderBy('id')->each(function (object $category): void {
            DB::table('product_categories')->where('id', $category->id)->update([
                'slug' => Str::slug($category->name).'-'.$category->id,
                'is_public' => (bool) $category->active,
            ]);
        });

        Schema::table('product_categories', function (Blueprint $table) {
            $table->unique('slug');
            $table->index(['active', 'is_public', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_categories', function (Blueprint $table) {
            $table->dropIndex(['active', 'is_public', 'sort_order']);
            $table->dropUnique(['slug']);
            $table->dropColumn(['slug', 'image_path', 'is_public', 'sort_order']);
        });
    }
};
