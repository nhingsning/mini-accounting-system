<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CreditNote extends Model
{
    protected $fillable = [
        'number', 'invoice_id', 'invoice_number', 'type', 'status', 'issue_date',
        'customer_name', 'customer_address', 'customer_tax_id', 'customer_branch_type', 'customer_branch_code',
        'reason', 'subtotal', 'tax', 'total', 'currency',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'subtotal'   => 'float',
        'tax'        => 'float',
        'total'      => 'float',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(CreditNoteItem::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

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

    public function scopeOfType($q, string $type)
    {
        return $q->where('type', $type);
    }
}
