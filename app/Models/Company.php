<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_name',
        'vat_number',
        'tax_code',
        'email',
        'phone',
        'address',
        'city',
        'postal_code',
        'province',
        'iban',
        'logo_path',
        'active',
    ];

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }
}
