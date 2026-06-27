<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecommendationLog extends Model
{
    // Only created_at exists in the schema — disable updated_at, keep created_at auto-populated
    public $timestamps = false;

    const CREATED_AT = 'created_at';

    protected $fillable = ['city_id', 'weather_status', 'reason', 'created_at'];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
}
