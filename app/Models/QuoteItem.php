<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuoteItem extends Model
{
    protected $table = 'quote_items';

    // ต้องมี 'price' และ 'quantity' ด้วย เพื่อกัน NOT NULL เด้ง
    protected $fillable = [
        'quotation_id',
        'description',
        'qty',
        'quantity',
        'unit',
        'unit_price',
        'price',
        'line_total',
    ];

    public $timestamps = true;

    public function quotation()
    {
        return $this->belongsTo(Quotation::class);
    }

    // กันพลาด: ตั้ง mutator ให้ sync คู่คอลัมน์เก่า/ใหม่อัตโนมัติ
    public function setQtyAttribute($value)
    {
        $this->attributes['qty'] = $value;
        $this->attributes['quantity'] = $value;
    }

    public function setUnitPriceAttribute($value)
    {
        $this->attributes['unit_price'] = $value;
        $this->attributes['price'] = $value;
    }
}
