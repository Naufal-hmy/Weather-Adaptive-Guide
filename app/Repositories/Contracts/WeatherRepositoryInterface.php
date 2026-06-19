<?php

namespace App\Repositories\Contracts;

interface WeatherRepositoryInterface
{
    public function findByCityId($cityId);
    public function updateWeather($cityId, array $data);
}
