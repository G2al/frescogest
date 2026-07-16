<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
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
            'display_name' => $this->display_name,
            'company_name' => $this->company_name,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'billing_address' => $this->billing_address,
            'delivery_address' => $this->delivery_address,
            'city' => $this->city,
            'postal_code' => $this->postal_code,
            'province' => $this->province,
            'vat_number' => $this->vat_number,
            'tax_code' => $this->tax_code,
            'notes' => $this->notes,
        ];
    }
}
