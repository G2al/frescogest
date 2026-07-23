<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->public_description,
            'brand' => $this->brand,
            'price_per_unit' => $this->effective_price_per_unit ?? $this->base_price_per_unit,
            'price_per_kg' => $this->effective_price_per_unit ?? $this->base_price_per_unit,
            'minimum_quantity' => $this->minimum_quantity ?? $this->base_minimum_quantity,
            'has_personalized_price' => (bool) ($this->has_personalized_price ?? false),
            'pricing_source' => $this->pricing_source ?? 'base',
            'discount_percentage' => $this->discount_percentage,
            'image_url' => $this->image_path ? Storage::disk('public')->url($this->image_path) : null,
            'is_seasonal' => $this->is_seasonal,
            'variants' => $this->whenLoaded('variants', fn () => $this->variants
                ->where('active', true)
                ->values()
                ->map(fn ($variant): array => [
                    'id' => $variant->id,
                    'sku' => $variant->sku,
                    'size' => $variant->size,
                    'color' => $variant->color,
                ])),
            'category' => ProductCategoryResource::make($this->whenLoaded('productCategory')),
            'unit_of_measure' => [
                'name' => $this->defaultUnitOfMeasure?->name,
                'symbol' => $this->defaultUnitOfMeasure?->symbol,
            ],
        ];
    }
}
