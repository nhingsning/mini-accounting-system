<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quotation extends Model
{
    protected $fillable = [
        'number','customer_name','issue_date','valid_until',
        'tax_rate','subtotal','tax','total','status','notes',
    ];

    protected $casts = [
        'issue_date'  => 'datetime',
        'valid_until' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(QuoteItem::class);
    }
}
