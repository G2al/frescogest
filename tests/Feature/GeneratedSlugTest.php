<?php

namespace Tests\Feature;

use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GeneratedSlugTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_slug_is_generated_when_omitted(): void
    {
        $category = ProductCategory::create([
            'name' => 'Frutta fresca',
            'active' => true,
        ]);

        $this->assertSame('frutta-fresca', $category->slug);
    }

    public function test_generated_slugs_are_unique(): void
    {
        ProductCategory::create(['name' => 'Frutta fresca', 'active' => true]);
        $category = ProductCategory::create(['name' => 'Frutta fresca', 'active' => true]);

        $this->assertSame('frutta-fresca-2', $category->slug);
    }
}
