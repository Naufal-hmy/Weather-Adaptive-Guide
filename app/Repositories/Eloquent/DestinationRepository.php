<?php

namespace App\Repositories\Eloquent;

use App\Models\Destination;
use App\Repositories\Contracts\DestinationRepositoryInterface;

class DestinationRepository implements DestinationRepositoryInterface
{
    public function all()
    {
        return Destination::with('city')->get();
    }

    public function findById($id)
    {
        return Destination::with('city')->find($id);
    }

    public function findByCity($cityId, $category = null)
    {
        $query = Destination::where('city_id', $cityId);
        
        if ($category === 'indoor') {
            $query->indoor();
        } elseif ($category === 'outdoor') {
            $query->outdoor();
        }

        return $query->get();
    }

    public function create(array $data)
    {
        return Destination::create($data);
    }

    public function update($id, array $data)
    {
        $destination = Destination::findOrFail($id);
        $destination->update($data);
        return $destination;
    }

    public function delete($id)
    {
        $destination = Destination::findOrFail($id);
        return $destination->delete();
    }
}
