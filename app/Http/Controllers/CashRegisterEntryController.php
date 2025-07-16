<?php

namespace App\Http\Controllers;

use App\Models\CashRegisterEntry;
use Illuminate\Http\Request;

class CashRegisterEntryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = CashRegisterEntry::query();
        
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }
        
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        
        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        
        $entries = $query->orderBy('created_at', 'desc')->get();
        return response()->json($entries);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:income,withdrawal',
            'amount' => 'required|numeric|min:0',
            'description' => 'required|string',
            'category' => 'required|string|max:255',
            'payment_method' => 'nullable|in:cash,card,transfer',
            'related_entity_id' => 'nullable|string|max:255',
            'related_entity_type' => 'nullable|in:customer,package,expense,other',
        ]);
        
        $validated['user_id'] = auth()->id() ?? 1; // Use authenticated user or default to admin
        
        $entry = CashRegisterEntry::create($validated);
        return response()->json($entry, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(CashRegisterEntry $cashRegister)
    {
        return response()->json($cashRegister);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CashRegisterEntry $cashRegister)
    {
        $validated = $request->validate([
            'type' => 'sometimes|in:income,withdrawal',
            'amount' => 'sometimes|numeric|min:0',
            'description' => 'sometimes|string',
            'category' => 'sometimes|string|max:255',
            'payment_method' => 'nullable|in:cash,card,transfer',
            'related_entity_id' => 'nullable|string|max:255',
            'related_entity_type' => 'nullable|in:customer,package,expense,other',
        ]);
        
        $cashRegister->update($validated);
        return response()->json($cashRegister);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CashRegisterEntry $cashRegister)
    {
        $cashRegister->delete();
        return response()->json(['message' => 'Cash register entry deleted successfully']);
    }
    
    /**
     * Display limited cash register entries for trainers (last 7 days only)
     */
    public function limitedIndex(Request $request)
    {
        $query = CashRegisterEntry::query()
            ->where('created_at', '>=', now()->subDays(7));
        
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }
        
        $entries = $query->orderBy('created_at', 'desc')->get();
        
        return response()->json([
            'data' => $entries,
            'limited' => true,
            'message' => 'Εμφανίζονται μόνο οι εγγραφές των τελευταίων 7 ημερών'
        ]);
    }
}
