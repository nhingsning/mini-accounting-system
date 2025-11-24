<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuotationLog extends Model
{
    protected $fillable = [
        'quotation_id',
        'action',
        'description',
        'user_id',
        'user_name',
    ];
}
