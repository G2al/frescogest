<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Correzione manuale di una riga del Resoconto Giornaliero. Sostituisce (non
 * somma) il valore calcolato dalle bolle reali per quel giorno/prodotto, senza
 * mai toccare le bolle/ordini/carichi partner da cui il resoconto è generato.
 */
class DailySummaryOverride extends Model
{
    protected $fillable = [
        'date',
        'product_id',
        'name',
        'symbol',
        'quantity',
        'net',
        'tax',
        'gross',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
        'quantity' => 'float',
        'net' => 'float',
        'tax' => 'float',
        'gross' => 'float',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
