<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecommendationLog extends Model
{
    public $timestamps = false;

    protected $fillable = ['city_id', 'weather_status', 'reason'];

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
}
