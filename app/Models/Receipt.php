<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Receipt extends Model
{
    use HasFactory;
    use SoftDeletes;

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
        'cancelled_at', 'cancellation_reason', 'status_before_cancellation',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'total' => 'decimal:2',
        'cancelled_at' => 'datetime',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
