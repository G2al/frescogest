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
        Schema::table('products', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('name');
            $table->string('image_path')->nullable()->after('description');
            $table->text('public_description')->nullable()->after('image_path');
            $table->boolean('is_public')->default(false)->after('public_description');
            $table->boolean('is_seasonal')->default(false)->after('is_public');
            $table->unsignedInteger('sort_order')->default(0)->after('is_seasonal');
        });

        DB::table('products')->orderBy('id')->each(function (object $product): void {
            DB::table('products')->where('id', $product->id)->update([
                'slug' => Str::slug($product->name).'-'.$product->id,
                'public_description' => $product->description,
                'is_public' => (bool) $product->active,
            ]);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->unique('slug');
            $table->index(['active', 'is_public', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['active', 'is_public', 'sort_order']);
            $table->dropUnique(['slug']);
            $table->dropColumn([
                'slug',
                'image_path',
                'public_description',
                'is_public',
                'is_seasonal',
                'sort_order',
            ]);
        });
    }
};
