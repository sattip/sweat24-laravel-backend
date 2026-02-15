<?php

namespace App\Http\Controllers;

use App\Models\BusinessExpense;
use Illuminate\Http\Request;

class BusinessExpenseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = BusinessExpense::with('store');

        // Filter by store if provided, otherwise show all stores
        if ($request->has('store_id') && $request->store_id) {
            $query->where('store_id', $request->store_id);
        }

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        if ($request->has('approved')) {
            $query->where('approved', $request->boolean('approved'));
        }

        if ($request->has('date_from')) {
            $query->whereDate('date', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('date', '<=', $request->date_to);
        }

        $expenses = $query->orderBy('date', 'desc')->paginate($request->get('per_page', 15));
        return response()->json($expenses);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'store_id' => 'required|exists:stores,id',
            'category' => 'required|in:utilities,equipment,maintenance,supplies,marketing,other',
            'subcategory' => 'required|string|max:255',
            'description' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'date' => 'required|date',
            'vendor' => 'nullable|string|max:255',
            'receipt' => 'nullable|string',
            'payment_method' => 'required|in:cash,card,transfer,iris,cash_a',
            'notes' => 'nullable|string',
        ]);

        $validated['approved'] = false; // Default to not approved

        $expense = BusinessExpense::create($validated);
        return response()->json($expense->load('store'), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(BusinessExpense $businessExpense)
    {
        return response()->json($businessExpense->load('store'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, BusinessExpense $businessExpense)
    {
        $validated = $request->validate([
            'store_id' => 'sometimes|exists:stores,id',
            'category' => 'sometimes|in:utilities,equipment,maintenance,supplies,marketing,other',
            'subcategory' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'amount' => 'sometimes|numeric|min:0',
            'date' => 'sometimes|date',
            'vendor' => 'nullable|string|max:255',
            'receipt' => 'nullable|string',
            'payment_method' => 'sometimes|in:cash,card,transfer,iris,cash_a',
            'approved' => 'sometimes|boolean',
            'approved_by' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $businessExpense->update($validated);
        return response()->json($businessExpense->load('store'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(BusinessExpense $businessExpense)
    {
        $businessExpense->delete();
        return response()->json(['message' => 'Business expense deleted successfully']);
    }
}
