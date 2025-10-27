<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    protected $fillable = [
        'number',
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
    ];

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
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

}
