<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class OrderItemResource extends JsonResource
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
            'product_id' => $this->product_id,
            'product_name' => $this->product_name,
            'product_variant_id' => $this->product_variant_id,
            'variant_sku' => $this->variant_sku,
            'variant_size' => $this->variant_size,
            'variant_color' => $this->variant_color,
            'quantity' => $this->quantity,
            'price_per_kg' => $this->price_per_kg,
            'unit_price_net' => $this->unit_price_net,
            'tax_percentage' => $this->tax_percentage,
            'line_total' => $this->line_total,
            'line_net' => $this->line_net,
            'line_tax' => $this->line_tax,
            'line_gross' => $this->line_gross,
            'unit_of_measure_name' => $this->unit_of_measure_name,
            'unit_of_measure_symbol' => $this->unit_of_measure_symbol,
            'image_url' => $this->product?->image_path
                ? Storage::disk('public')->url($this->product->image_path)
                : null,
        ];
    }
}
