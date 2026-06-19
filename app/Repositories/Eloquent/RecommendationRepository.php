<?php

namespace App\Repositories\Eloquent;

use App\Models\Recommendation;
use App\Models\RecommendationLog;
use App\Repositories\Contracts\RecommendationRepositoryInterface;

class RecommendationRepository implements RecommendationRepositoryInterface
{
    public function getRecommendationByCity($cityId)
    {
        return Recommendation::where('city_id', $cityId)->first();
    }

    public function updateOrCreateRecommendation($cityId, array $data)
    {
        return Recommendation::updateOrCreate(
            ['city_id' => $cityId],
            $data
        );
    }

    public function getLogsByCity($cityId)
    {
        return RecommendationLog::where('city_id', $cityId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function createLog(array $data)
    {
        return RecommendationLog::create($data);
    }
}
