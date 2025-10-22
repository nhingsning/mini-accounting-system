<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    protected $fillable = [
        'invoice_id','description','qty','unit','price','line_total',
    ];

    protected $casts = [
        'qty'        => 'integer',
        'price'      => 'float',
        'line_total' => 'float',
    ];

    public function invoice()
    {
        return $this->belongsTo(\App\Models\Invoice::class);
    }
}
