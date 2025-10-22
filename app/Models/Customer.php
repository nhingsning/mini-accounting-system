<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
 protected $fillable = [
        'name','name_hidden','address','address_hidden','tax_id','tel','fax',
        'payment_terms','is_branch','branch_code',
        'contact_name','contact_department','contact_position','contact_mobile','contact_email',
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
