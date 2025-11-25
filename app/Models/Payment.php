<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\BankStatement;

class Payment extends Model
{
    protected $fillable = [
        'invoice_id',
        'amount',
        'currency',
        'method',
        'reference',
        'note',
        'paid_at',
        'status',
        'slip_path',
        'bank_statement_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function bankStatement(): HasOne
    {
        return $this->hasOne(BankStatement::class, 'matched_payment_id');
    }

    protected static function booted(): void
    {
        $syncInvoice = function (Payment $payment) {
            $invoice = $payment->invoice;
            if ($invoice) {
                $invoice->recalculatePaymentTotals();
            }
        };

        static::saved($syncInvoice);
        static::deleted($syncInvoice);
    }
}
