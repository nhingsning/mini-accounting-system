<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
protected $fillable = [
  'number','customer_name','issue_date','due_date',
  'tax_rate','subtotal','tax','total','status','notes'
];


    protected $casts = [
        'issue_date' => 'date',
        'due_date'   => 'date',
        'tax_rate'   => 'decimal:2',
        'subtotal'   => 'decimal:2',
        'tax'        => 'decimal:2',
        'total'      => 'decimal:2',
    ];

    public function items()
{
    return $this->hasMany(\App\Models\InvoiceItem::class);
}

}
