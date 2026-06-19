<?php

namespace App\Repositories\Contracts;

interface CityRepositoryInterface
{
    public function all();
    public function findById($id);
    public function findByName($name);
    public function create(array $data);
}
