<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\CashRegisterEntry;
use App\Services\CashRegisterService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class StoreController extends Controller
{
    protected $cashRegisterService;

    public function __construct(CashRegisterService $cashRegisterService)
    {
        $this->cashRegisterService = $cashRegisterService;
    }

    /**
     * Get store cash entries with filters
     */
    public function getCashEntries(Request $request, int $storeId): JsonResponse
    {
        $store = Store::findOrFail($storeId);

        $query = CashRegisterEntry::where('store_id', $storeId);

        // Apply filters
        if ($request->has('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        $entries = $query->with(['user:id,name,email'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 50));

        return response()->json([
            'success' => true,
            'data' => $entries,
            'store' => $store->only(['id', 'name'])
        ]);
    }

    /**
     * Get store financial report
     */
    public function getReport(Request $request, int $storeId): JsonResponse
    {
        $store = Store::findOrFail($storeId);

        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        $summary = $this->cashRegisterService->getStoreSummary($storeId, $startDate, $endDate);

        return response()->json([
            'success' => true,
            'data' => $summary,
            'store' => $store->only(['id', 'name'])
        ]);
    }

    /**
     * Record an expense for a store
     */
    public function recordExpense(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'store_id' => 'required|exists:stores,id',
            'amount' => 'required|numeric|min:0.01',
            'category' => 'required|string|max:255',
            'description' => 'required|string|max:1000',
            'payment_method' => 'nullable|in:cash,card,transfer'
        ]);

        $expense = $this->cashRegisterService->recordExpense($validated);

        return response()->json([
            'success' => true,
            'message' => 'Expense recorded successfully',
            'data' => $expense->load(['user:id,name,email', 'store:id,name'])
        ], 201);
    }
}
