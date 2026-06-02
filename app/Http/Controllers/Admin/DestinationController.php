<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Destination;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DestinationController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Destinations', [
            'destinations' => Destination::orderBy('created_at', 'desc')->get()
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'category' => 'required|in:indoor,outdoor',
            'city' => 'required|string|max:255',
            'image_url' => 'nullable|string|max:255',
            'image_file' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'min_temp' => 'nullable|integer',
            'max_temp' => 'nullable|integer',
        ]);

        if ($request->hasFile('image_file')) {
            $path = $request->file('image_file')->store('destinations', 'public');
            $validated['image_url'] = '/storage/' . $path;
        }

        Destination::create($validated);

        return redirect()->back()->with('success', 'Destinasi berhasil ditambahkan.');
    }

    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'category' => 'required|in:indoor,outdoor',
            'city' => 'required|string|max:255',
            'image_url' => 'nullable|string|max:255',
            'image_file' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'min_temp' => 'nullable|integer',
            'max_temp' => 'nullable|integer',
        ]);

        $destination = Destination::findOrFail($id);

        if ($request->hasFile('image_file')) {
            $path = $request->file('image_file')->store('destinations', 'public');
            $validated['image_url'] = '/storage/' . $path;
        }

        $destination->update($validated);

        return redirect()->back()->with('success', 'Destinasi berhasil diperbarui.');
    }

    public function destroy(string $id)
    {
        $destination = Destination::findOrFail($id);
        $destination->delete();

        return redirect()->back()->with('success', 'Destinasi berhasil dihapus.');
    }
}
