<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class StoresController extends Controller
{
    /**
     * Display a listing of stores
     */
    public function index(Request $request): JsonResponse
    {
        $query = Store::query();

        // Filter by active status
        if ($request->has('active')) {
            $query->where('is_active', $request->boolean('active'));
        }

        // Order by name
        $stores = $query->orderBy('name', 'asc')->get();

        return response()->json([
            'success' => true,
            'data' => $stores
        ]);
    }

    /**
     * Store a newly created store
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'color' => 'nullable|string|regex:/^#[a-fA-F0-9]{6}$/',
            'is_active' => 'boolean',
            'description' => 'nullable|string|max:1000',
            'working_hours' => 'nullable|array'
        ]);

        $store = Store::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Το κατάστημα δημιουργήθηκε επιτυχώς',
            'data' => $store
        ], 201);
    }

    /**
     * Display the specified store
     */
    public function show(Store $store): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $store
        ]);
    }

    /**
     * Update the specified store
     */
    public function update(Request $request, Store $store): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'color' => 'nullable|string|regex:/^#[a-fA-F0-9]{6}$/',
            'is_active' => 'boolean',
            'description' => 'nullable|string|max:1000',
            'working_hours' => 'nullable|array'
        ]);

        $store->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Το κατάστημα ενημερώθηκε επιτυχώς',
            'data' => $store
        ]);
    }

    /**
     * Remove the specified store
     */
    public function destroy(Store $store): JsonResponse
    {
        // Check if store has related records
        if ($store->classes()->count() > 0 || $store->bookings()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Δεν μπορείτε να διαγράψετε αυτό το κατάστημα γιατί έχει σχετικές κρατήσεις ή μαθήματα'
            ], 422);
        }

        $store->delete();

        return response()->json([
            'success' => true,
            'message' => 'Το κατάστημα διαγράφηκε επιτυχώς'
        ]);
    }

    /**
     * Toggle store active status
     */
    public function toggleActive(Store $store): JsonResponse
    {
        $store->update(['is_active' => !$store->is_active]);

        return response()->json([
            'success' => true,
            'message' => 'Η κατάσταση του καταστήματος ενημερώθηκε επιτυχώς',
            'data' => $store
        ]);
    }
}



