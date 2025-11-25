<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class DocumentApproval extends Model
{
    protected $fillable = [
        'approvable_type',
        'approvable_id',
        'step',
        'role',
        'status',
        'comment',
        'acted_at',
        'user_id',
    ];

    protected $casts = [
        'acted_at' => 'datetime',
    ];

    public function approvable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
