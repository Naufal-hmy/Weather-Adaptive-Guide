<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Destination extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'category',
        'city',
        'image_url',
        'min_temp',
        'max_temp'
    ];

    public function scopeIndoor($query)
    {
        return $query->where('category', 'indoor');
    }

    public function scopeOutdoor($query)
    {
        return $query->where('category', 'outdoor');
    }
}
