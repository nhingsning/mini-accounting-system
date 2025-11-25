<?php

namespace App\Models;

use App\Models\Receipt;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Schema;

class Invoice extends Model
{
    protected $fillable = [
        'number', 'quotation_id', 'quotation_number',
        'customer_id', 'customer_name', 'customer_address', 'customer_tax_id',
        'customer_branch_type', 'customer_branch_code',
        'issue_date', 'due_date',
        'discount_percent', 'vat_enabled', 'tax_rate',
        'subtotal', 'tax', 'total',
        'status', 'currency',
    ];

    protected $casts = [
        'issue_date'       => 'date',
        'due_date'         => 'date',
        'discount_percent' => 'float',
        'vat_enabled'      => 'bool',
        'tax_rate'         => 'float',
        'subtotal'         => 'float',
        'tax'              => 'float',
        'total'            => 'float',
        'paid_total'       => 'float',
        'outstanding_total'=> 'float',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function receipt(): HasOne
    {
        return $this->hasOne(Receipt::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    // ใช้เลขเอกสาร (หรือ id) สำหรับ route model binding
    public function getRouteKeyName(): string
    {
        return 'number';
    }

    public function resolveRouteBinding($value, $field = null)
    {
        if ($field) {
            return parent::resolveRouteBinding($value, $field);
        }
        return $this->where('number', $value)->orWhere('id', $value)->firstOrFail();
    }

    // ค้นหาแบบง่าย ๆ
    public function scopeSearch($q, ?string $term)
    {
        if (!filled($term)) return $q;

        $term = trim($term);

        return $q->where(function ($qq) use ($term) {
            $qq->where('number', 'like', "%{$term}%")
               ->orWhere('customer_name', 'like', "%{$term}%")
               ->orWhere('status', 'like', "%{$term}%")
               ->orWhere('total', 'like', "%{$term}%");
        });
    }

    /**
     * จำกัดผลลัพธ์ให้เป็นใบแจ้งหนี้ (ไม่รวม PO ที่แชร์ตาราง invoices)
     */
    public function scopeOnlyInvoices($q)
    {
        return $q->where(function ($qq) {
            $qq->whereNull('number')
                ->orWhere('number', 'not like', 'PO%');
        });
    }

    public function recalculatePaymentTotals(): void
    {
        if (!Schema::hasTable('payments')) {
            return;
        }

        $paid = $this->payments()
            ->whereNotIn('status', ['void'])
            ->sum('amount');

        $total = (float) ($this->total ?? 0);
        $outstanding = max(0, $total - (float) $paid);

        $this->paid_total = $paid;
        $this->outstanding_total = $outstanding;

        if (Schema::hasColumn('invoices', 'paid_total') && Schema::hasColumn('invoices', 'outstanding_total')) {
            $this->forceFill([
                'paid_total'        => $paid,
                'outstanding_total' => $outstanding,
            ])->saveQuietly();
            $this->refresh();
        }

        $this->updateStatusFromPayments();
    }

    public function updateStatusFromPayments(): void
    {
        $status = strtolower($this->status ?? 'pending');
        if (in_array($status, ['cancelled', 'void'])) {
            return;
        }

        $paid = (float) ($this->paid_total ?? 0);
        $total = (float) ($this->total ?? 0);
        $outstanding = $this->outstanding_total ?? max(0, $total - $paid);

        if ($total > 0 && $paid >= $total - 0.01) {
            $new = 'paid';
        } elseif ($paid > 0) {
            $new = 'partial';
        } else {
            $new = 'pending';
        }

        if ($status !== $new) {
            $this->forceFill(['status' => $new])->saveQuietly();
        }

        if ($this->receipt && in_array($new, ['paid', 'partial'])) {
            $this->receipt->forceFill([
                'status' => $new === 'paid' ? 'issued' : 'draft',
            ])->saveQuietly();
        }
    }

}
