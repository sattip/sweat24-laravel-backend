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
        $query = BusinessExpense::query();
        
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
        
        $expenses = $query->orderBy('date', 'desc')->get();
        return response()->json($expenses);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'category' => 'required|in:utilities,equipment,maintenance,supplies,marketing,other',
            'subcategory' => 'required|string|max:255',
            'description' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'date' => 'required|date',
            'vendor' => 'nullable|string|max:255',
            'receipt' => 'nullable|string',
            'payment_method' => 'required|in:cash,card,transfer',
            'notes' => 'nullable|string',
        ]);
        
        $validated['approved'] = false; // Default to not approved
        
        $expense = BusinessExpense::create($validated);
        return response()->json($expense, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(BusinessExpense $businessExpense)
    {
        return response()->json($businessExpense);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, BusinessExpense $businessExpense)
    {
        $validated = $request->validate([
            'category' => 'sometimes|in:utilities,equipment,maintenance,supplies,marketing,other',
            'subcategory' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'amount' => 'sometimes|numeric|min:0',
            'date' => 'sometimes|date',
            'vendor' => 'nullable|string|max:255',
            'receipt' => 'nullable|string',
            'payment_method' => 'sometimes|in:cash,card,transfer',
            'approved' => 'sometimes|boolean',
            'approved_by' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);
        
        $businessExpense->update($validated);
        return response()->json($businessExpense);
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
