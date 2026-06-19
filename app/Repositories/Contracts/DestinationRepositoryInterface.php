<?php

namespace App\Repositories\Contracts;

interface DestinationRepositoryInterface
{
    public function all();
    public function findById($id);
    public function findByCity($cityId, $category = null);
    public function create(array $data);
    public function update($id, array $data);
    public function delete($id);
}
