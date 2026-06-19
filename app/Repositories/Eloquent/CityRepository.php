<?php

namespace App\Repositories\Eloquent;

use App\Models\City;
use App\Repositories\Contracts\CityRepositoryInterface;

class CityRepository implements CityRepositoryInterface
{
    public function all()
    {
        return City::with('weatherCondition')->get();
    }

    public function findById($id)
    {
        return City::with('weatherCondition')->find($id);
    }

    public function findByName($name)
    {
        return City::with('weatherCondition')->where('name', 'like', "%{$name}%")->first();
    }

    public function create(array $data)
    {
        return City::create($data);
    }
}
