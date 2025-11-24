<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditNoteItem extends Model
{
    protected $fillable = [
        'credit_note_id', 'description', 'qty', 'unit_price', 'line_total', 'unit',
    ];

    protected $casts = [
        'qty'        => 'float',
        'unit_price' => 'float',
        'line_total' => 'float',
    ];

    public function creditNote(): BelongsTo
    {
        return $this->belongsTo(CreditNote::class);
    }
}
