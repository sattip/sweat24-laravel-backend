<?php

namespace App\Http\Controllers;

use App\Models\SpecializedService;
use Illuminate\Http\Request;

class SpecializedServiceController extends Controller
{
    public function index()
    {
        $services = SpecializedService::where('is_active', true)
            ->orderBy('display_order')
            ->get();
        
        return response()->json($services);
    }

    // Admin method to get all services (including inactive)
    public function adminIndex()
    {
        $services = SpecializedService::orderBy('display_order')->get();
        return response()->json($services);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'duration' => 'required|string',
            'price' => 'required|string',
            'icon' => 'nullable|string',
            'preferred_time_slots' => 'nullable|array',
            'is_active' => 'boolean',
            'display_order' => 'integer',
        ]);

        // Generate slug from name
        $validated['slug'] = \Str::slug($validated['name']);

        $service = SpecializedService::create($validated);
        return response()->json($service, 201);
    }

    public function show(SpecializedService $specializedService)
    {
        return response()->json($specializedService);
    }

    public function update(Request $request, SpecializedService $specializedService)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'duration' => 'sometimes|string',
            'price' => 'sometimes|string',
            'icon' => 'nullable|string',
            'preferred_time_slots' => 'nullable|array',
            'is_active' => 'boolean',
            'display_order' => 'integer',
        ]);

        // Update slug if name changes
        if (isset($validated['name'])) {
            $validated['slug'] = \Str::slug($validated['name']);
        }

        $specializedService->update($validated);
        return response()->json($specializedService);
    }

    public function destroy(SpecializedService $specializedService)
    {
        $specializedService->delete();
        return response()->json(['message' => 'Service deleted successfully']);
    }
}
