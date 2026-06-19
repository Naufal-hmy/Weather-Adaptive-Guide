<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Destination extends Model
{
    use HasFactory;

    protected $fillable = [
        'city_id',
        'name',
        'description',
        'category',
        'image_url',
        'opening_hours',
        'rating',
        'min_temp',
        'max_temp'
    ];

    protected $casts = [
        'rating' => 'float',
    ];

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function scopeIndoor($query)
    {
        return $query->where('category', 'indoor');
    }

    public function scopeOutdoor($query)
    {
        return $query->where('category', 'outdoor');
    }
}
