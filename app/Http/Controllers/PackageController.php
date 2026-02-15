<?php

namespace App\Http\Controllers;

use App\Models\Package;
use Illuminate\Http\Request;

class PackageController extends Controller
{
    public function index()
    {
        $packages = Package::with('services')->get();
        return response()->json($packages);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'sessions' => 'nullable|integer|min:1',
            'duration' => 'required|integer|min:1',
            'service_ids' => 'nullable|array',
            'service_ids.*' => 'exists:services,id',
            'status' => 'sometimes|in:active,inactive',
            'description' => 'nullable|string',
            'class_type' => 'nullable|string',
            'class_types' => 'nullable|array',
            'class_types.*' => 'string',
            'time_restriction_enabled' => 'nullable|boolean',
            'booking_start_time' => 'nullable|date_format:H:i',
            'booking_end_time' => 'nullable|date_format:H:i',
        ]);

        $packageData = collect($validated)->except('service_ids')->toArray();
        $package = Package::create($packageData);

        // Σύνδεση των υπηρεσιών με το πακέτο
        if (isset($validated['service_ids'])) {
            $package->services()->sync($validated['service_ids']);
        }

        $package->load('services');
        return response()->json($package, 201);
    }

    public function show(Package $package)
    {
        $package->load('services');
        return response()->json($package);
    }

    public function update(Request $request, Package $package)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'price' => 'sometimes|numeric|min:0',
            'sessions' => 'nullable|integer|min:1',
            'duration' => 'sometimes|integer|min:1',
            'service_ids' => 'nullable|array',
            'service_ids.*' => 'exists:services,id',
            'status' => 'sometimes|in:active,inactive',
            'description' => 'nullable|string',
            'class_type' => 'nullable|string',
            'class_types' => 'nullable|array',
            'class_types.*' => 'string',
            'time_restriction_enabled' => 'nullable|boolean',
            'booking_start_time' => 'nullable|date_format:H:i',
            'booking_end_time' => 'nullable|date_format:H:i',
        ]);

        $packageData = collect($validated)->except('service_ids')->toArray();
        $package->update($packageData);

        // Σύνδεση των υπηρεσιών με το πακέτο
        if (isset($validated['service_ids'])) {
            $package->services()->sync($validated['service_ids']);
        }

        $package->load('services');
        return response()->json($package);
    }

    public function destroy(Package $package)
    {
        $package->delete();
        return response()->json(['message' => 'Package deleted successfully']);
    }
}