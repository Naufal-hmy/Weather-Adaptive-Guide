<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\DestinationRepositoryInterface;
use App\Repositories\Contracts\CityRepositoryInterface;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DestinationController extends Controller
{
    protected $destinationRepo;
    protected $cityRepo;

    public function __construct(
        DestinationRepositoryInterface $destinationRepo,
        CityRepositoryInterface $cityRepo
    ) {
        $this->destinationRepo = $destinationRepo;
        $this->cityRepo = $cityRepo;
    }

    public function index()
    {
        return Inertia::render('Admin/Destinations', [
            'destinations' => $this->destinationRepo->all(),
            'cities' => $this->cityRepo->all(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'category' => 'required|in:indoor,outdoor',
            'city_id' => 'required|exists:cities,id',
            'image_url' => 'nullable|string|max:255',
            'image_file' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'opening_hours' => 'nullable|string|max:255',
            'rating' => 'nullable|numeric|between:0,5',
            'min_temp' => 'nullable|integer',
            'max_temp' => 'nullable|integer',
        ]);

        if ($request->hasFile('image_file')) {
            $path = $request->file('image_file')->store('destinations', 'public');
            $validated['image_url'] = '/storage/' . $path;
        }

        $this->destinationRepo->create($validated);

        return redirect()->back()->with('success', 'Destinasi berhasil ditambahkan.');
    }

    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'category' => 'required|in:indoor,outdoor',
            'city_id' => 'required|exists:cities,id',
            'image_url' => 'nullable|string|max:255',
            'image_file' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'opening_hours' => 'nullable|string|max:255',
            'rating' => 'nullable|numeric|between:0,5',
            'min_temp' => 'nullable|integer',
            'max_temp' => 'nullable|integer',
        ]);

        if ($request->hasFile('image_file')) {
            $path = $request->file('image_file')->store('destinations', 'public');
            $validated['image_url'] = '/storage/' . $path;
        }

        $this->destinationRepo->update($id, $validated);

        return redirect()->back()->with('success', 'Destinasi berhasil diperbarui.');
    }

    public function destroy(string $id)
    {
        $this->destinationRepo->delete($id);
        return redirect()->back()->with('success', 'Destinasi berhasil dihapus.');
    }
}
