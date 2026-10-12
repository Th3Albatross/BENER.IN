<?php

namespace App\Models;

use Database\Factories\ReportFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use HasFactory;

    protected $fillable = [
        'identifier',
        'status',
        'severity',
        'categories_id',
        'users_id',
        'facilities_id',
        'title',
        'location_description',
        'description',
        'incident_date',
        'created_at',
        'updated_at',
        'edit_count'
    ];
}
