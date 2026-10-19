<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Response extends Model
{
    protected $fillable = [
        'users_id',
        'reports_id',
        'message',
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class, 'reports_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'users_id');
    }
}
