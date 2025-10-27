<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = [
        'name',
        'name_private',
        'tax_id',
        'tel',
        'fax',
        'payment_terms',
        'address_show',
        'address_private',
        'office_type',
        'branch_name',
        'branch_code',
        'head_office_id',
    ];

    public function quotations(){ return $this->hasMany(Quotation::class); }
    public function invoices(){ return $this->hasMany(Invoice::class); }

    public function contacts(){ return $this->hasMany(CustomerContact::class); }

    // ความสัมพันธ์สำนักงานใหญ่/สาขา
    public function headOffice(){ return $this->belongsTo(Customer::class, 'head_office_id'); }
    public function branches(){ return $this->hasMany(Customer::class, 'head_office_id'); }

    public function getLabelAttribute(): string
    {
        return $this->tax_id ? "{$this->name} ({$this->tax_id})" : $this->name;
    }
}
