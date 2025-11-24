<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Receipt extends Model
{
    use HasFactory;

    protected $fillable = [
        'number',
        'invoice_id',
        'invoice_number',
        'customer_id',
        'customer_name',
        'customer_address',
        'customer_tax_id',
        'customer_branch_type',
        'customer_branch_code',
        'issue_date',
        'total',
        'status',
        'currency',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'total' => 'decimal:2',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
