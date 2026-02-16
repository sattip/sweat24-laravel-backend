<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Location;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LocationsController extends Controller
{
    /**
     * Display a listing of locations.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Location::query();

        // Filter by active status if requested
        if ($request->has('active_only') && $request->active_only) {
            $query->active();
        }

        // Order by sort_order
        $locations = $query->ordered()->get();

        return response()->json([
            'success' => true,
            'data' => $locations
        ]);
    }

    /**
     * Store a newly created location.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255|unique:locations,code',
            'address' => 'nullable|string',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        $location = Location::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Location created successfully',
            'data' => $location
        ], 201);
    }

    /**
     * Display the specified location.
     */
    public function show(string $id): JsonResponse
    {
        $location = Location::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $location
        ]);
    }

    /**
     * Update the specified location.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $location = Location::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('locations', 'code')->ignore($location->id)
            ],
            'address' => 'nullable|string',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        $location->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Location updated successfully',
            'data' => $location
        ]);
    }

    /**
     * Remove the specified location.
     */
    public function destroy(string $id): JsonResponse
    {
        $location = Location::findOrFail($id);
        $location->delete();

        return response()->json([
            'success' => true,
            'message' => 'Location deleted successfully'
        ]);
    }
}
