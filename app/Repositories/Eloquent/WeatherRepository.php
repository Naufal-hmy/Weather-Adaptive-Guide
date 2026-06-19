<?php

namespace App\Repositories\Eloquent;

use App\Models\WeatherCondition;
use App\Repositories\Contracts\WeatherRepositoryInterface;

class WeatherRepository implements WeatherRepositoryInterface
{
    public function findByCityId($cityId)
    {
        return WeatherCondition::where('city_id', $cityId)->first();
    }

    public function updateWeather($cityId, array $data)
    {
        $weather = WeatherCondition::updateOrCreate(
            ['city_id' => $cityId],
            $data
        );
        return $weather;
    }
}
