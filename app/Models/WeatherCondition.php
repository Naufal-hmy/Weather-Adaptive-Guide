<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeatherCondition extends Model
{
    protected $fillable = ['city_id', 'status', 'temperature', 'humidity', 'wind_speed'];

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
}
