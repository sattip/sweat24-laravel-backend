<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Traits\SearchableTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class StoreManagementController extends Controller
{
    use SearchableTrait;
    /**
     * Display a listing of stores.
     */
    public function index(Request $request)
    {
        $query = Store::query();

        // Filter by active status if specified
        if ($request->has('active')) {
            $query->where('is_active', $request->boolean('active'));
        }

        // Search by name or email if specified
        if ($request->has('search')) {
            $this->addSafeMultiColumnSearch($query, ['name', 'email'], $request->search);
        }

        $stores = $query->orderBy('name')->paginate($request->get('per_page', 15));

        return response()->json($stores);
    }

    /**
     * Store a newly created store.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:stores,name',
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'description' => 'nullable|string|max:1000',
            'email' => 'nullable|email|max:255',
            'is_active' => 'boolean',
            'color' => 'nullable|string|max:7|regex:/^#[a-fA-F0-9]{6}$/',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $store = Store::create([
            'name' => $request->name,
            'address' => $request->address,
            'is_active' => $request->is_active ?? true,
            'color' => $request->color ?? '#3B82F6',
        ]);

        return response()->json([
            'message' => 'Store created successfully',
            'store' => $store
        ], 201);
    }

    /**
     * Display the specified store.
     */
    public function show(Store $store)
    {
        return response()->json([
            'store' => $store->load([
                'cashRegisterEntries' => function($query) {
                    $query->latest()->limit(10);
                },
                'bookings' => function($query) {
                    $query->latest()->limit(10);
                },
                'businessExpenses' => function($query) {
                    $query->latest()->limit(10);
                }
            ])
        ]);
    }

    /**
     * Update the specified store.
     */
    public function update(Request $request, Store $store)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255|unique:stores,name,' . $store->id,
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'description' => 'nullable|string|max:1000',
            'email' => 'nullable|email|max:255',
            'is_active' => 'boolean',
            'color' => 'nullable|string|max:7|regex:/^#[a-fA-F0-9]{6}$/',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $store->update($request->only(['name', 'address', 'phone', 'description', 'email', 'is_active', 'color']));

        return response()->json([
            'message' => 'Store updated successfully',
            'store' => $store
        ]);
    }

    /**
     * Remove the specified store.
     */
    public function destroy(Store $store)
    {
        // Check if store has related records
        $hasBookings = $store->bookings()->exists();
        $hasCashEntries = $store->cashRegisterEntries()->exists();
        $hasExpenses = $store->businessExpenses()->exists();

        if ($hasBookings || $hasCashEntries || $hasExpenses) {
            return response()->json([
                'message' => 'Cannot delete store with existing records',
                'details' => [
                    'bookings' => $hasBookings,
                    'cash_entries' => $hasCashEntries,
                    'expenses' => $hasExpenses
                ]
            ], 422);
        }

        $store->delete();

        return response()->json([
            'message' => 'Store deleted successfully'
        ]);
    }

    /**
     * Toggle store active status.
     */
    public function toggleStatus(Store $store)
    {
        $store->update([
            'is_active' => !$store->is_active
        ]);

        return response()->json([
            'message' => 'Store status updated successfully',
            'store' => $store
        ]);
    }

    /**
     * Get store statistics.
     */
    public function statistics(Store $store)
    {
        $stats = [
            'total_bookings' => $store->bookings()->count(),
            'active_bookings' => $store->bookings()->where('status', 'confirmed')->count(),
            'total_revenue' => $store->cashRegisterEntries()->where('type', 'income')->sum('amount'),
            'total_expenses' => $store->businessExpenses()->sum('amount'),
            'cash_register_entries' => $store->cashRegisterEntries()->count(),
            'recent_activity' => $store->cashRegisterEntries()->latest()->limit(5)->get()
        ];

        return response()->json([
            'store' => $store,
            'statistics' => $stats
        ]);
    }
}