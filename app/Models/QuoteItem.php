<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuoteItem extends Model
{
    protected $fillable = ['description','qty','unit','price','line_total'];

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }
}
