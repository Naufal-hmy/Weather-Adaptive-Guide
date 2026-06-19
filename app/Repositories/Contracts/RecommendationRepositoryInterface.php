<?php

namespace App\Repositories\Contracts;

interface RecommendationRepositoryInterface
{
    public function getRecommendationByCity($cityId);
    public function updateOrCreateRecommendation($cityId, array $data);
    public function getLogsByCity($cityId);
    public function createLog(array $data);
}
