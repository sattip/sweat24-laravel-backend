<?php

namespace App\Http\Controllers;

use App\Models\Package;
use Illuminate\Http\Request;

class PackageController extends Controller
{
    public function index()
    {
        $packages = Package::all();
        return response()->json($packages);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'sessions' => 'nullable|integer|min:1',
            'duration' => 'required|integer|min:1',
            'type' => 'required|string|max:255',
            'status' => 'sometimes|in:active,inactive',
            'description' => 'nullable|string',
        ]);

        $package = Package::create($validated);
        return response()->json($package, 201);
    }

    public function show(Package $package)
    {
        return response()->json($package);
    }

    public function update(Request $request, Package $package)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'price' => 'sometimes|numeric|min:0',
            'sessions' => 'nullable|integer|min:1',
            'duration' => 'sometimes|integer|min:1',
            'type' => 'sometimes|string|max:255',
            'status' => 'sometimes|in:active,inactive',
            'description' => 'nullable|string',
        ]);

        $package->update($validated);
        return response()->json($package);
    }

    public function destroy(Package $package)
    {
        $package->delete();
        return response()->json(['message' => 'Package deleted successfully']);
    }
}