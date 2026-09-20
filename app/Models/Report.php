<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    protected $fillable = [
        'status',
        'severity',
        'users_id',
        'facility_id',
        'responses_id',
        'title',
        'description',
        'edit_count'
    ];
}
