<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuotationAttachment extends Model
{
    protected $fillable = [
        'quotation_id','path','original_name','mime_type','size'
    ];

    public function quotation()
    {
        return $this->belongsTo(Quotation::class);
    }
}
