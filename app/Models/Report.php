<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'edit_count',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'users_id');
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class, 'facilities_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'categories_id');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(Response::class, 'reports_id');
    }
}
