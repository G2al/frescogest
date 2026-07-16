<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
            'quantity' => $this->quantity,
            'price_per_kg' => $this->price_per_kg,
            'line_total' => $this->line_total,
            'unit_of_measure_name' => $this->unit_of_measure_name,
            'unit_of_measure_symbol' => $this->unit_of_measure_symbol,
        ];
    }
}
