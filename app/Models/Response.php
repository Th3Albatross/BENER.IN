<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Response extends Model
{
    protected $fillable = [
        'users_id',
        'reports_id',
        'message',
    ];
}
