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
        $query = CashRegisterEntry::with('store', 'user:id,name,email');

        // Filter by store if provided, otherwise show all stores
        if ($request->has('store_id') && $request->store_id) {
            $query->where('store_id', $request->store_id);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $entries = $query->orderBy('created_at', 'desc')->paginate($request->get('per_page', 15));
        return response()->json($entries);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'store_id' => 'required|exists:stores,id',
            'type' => 'required|in:income,withdrawal',
            'amount' => 'required|numeric|min:0',
            'description' => 'required|string',
            'category' => 'required|string|max:255',
            'payment_method' => 'nullable|in:cash,card,transfer,iris,cash_a,manolis,χρωστούμενα_ρέστα',
            'related_entity_id' => 'nullable|string|max:255',
            'related_entity_type' => 'nullable|in:customer,package,expense,other',
        ]);

        $validated['user_id'] = auth()->id() ?? 1; // Use authenticated user or default to admin

        $entry = CashRegisterEntry::create($validated);
        return response()->json($entry->load('store', 'user:id,name,email'), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(CashRegisterEntry $cashRegister)
    {
        return response()->json($cashRegister->load('store', 'user:id,name,email'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CashRegisterEntry $cashRegister)
    {
        $validated = $request->validate([
            'store_id' => 'sometimes|exists:stores,id',
            'type' => 'sometimes|in:income,withdrawal',
            'amount' => 'sometimes|numeric|min:0',
            'description' => 'sometimes|string',
            'category' => 'sometimes|string|max:255',
            'payment_method' => 'nullable|in:cash,card,transfer,iris,cash_a,manolis,χρωστούμενα_ρέστα',
            'related_entity_id' => 'nullable|string|max:255',
            'related_entity_type' => 'nullable|in:customer,package,expense,other',
        ]);

        $cashRegister->update($validated);
        return response()->json($cashRegister->load('store', 'user:id,name,email'));
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
        $query = CashRegisterEntry::with('store', 'user:id,name,email')
            ->where('created_at', '>=', now()->subDays(7));

        // Filter by store if provided, otherwise show all stores
        if ($request->has('store_id') && $request->store_id) {
            $query->where('store_id', $request->store_id);
        }

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

    /**
     * Get summary of cash register entries by store
     */
    public function summary(Request $request)
    {
        $entries = CashRegisterEntry::with('store')
            ->selectRaw('
                store_id,
                COUNT(*) as total_entries,
                SUM(CASE WHEN type = "income" THEN amount ELSE 0 END) as total_income,
                SUM(CASE WHEN type = "withdrawal" THEN amount ELSE 0 END) as total_expenses
            ')
            ->whereNotNull('store_id')
            ->groupBy('store_id')
            ->get();

        $unknownEntries = CashRegisterEntry::whereNull('store_id')
            ->selectRaw('
                COUNT(*) as total_entries,
                SUM(CASE WHEN type = "income" THEN amount ELSE 0 END) as total_income,
                SUM(CASE WHEN type = "withdrawal" THEN amount ELSE 0 END) as total_expenses
            ')
            ->first();

        return response()->json([
            'stores' => $entries->map(function($entry) {
                return [
                    'store_id' => $entry->store_id,
                    'store_name' => $entry->store->name,
                    'store_color' => $entry->store->color,
                    'total_entries' => $entry->total_entries,
                    'total_income' => $entry->total_income,
                    'total_expenses' => $entry->total_expenses
                ];
            }),
            'unknown' => $unknownEntries ? [
                'total_entries' => $unknownEntries->total_entries,
                'total_income' => $unknownEntries->total_income,
                'total_expenses' => $unknownEntries->total_expenses
            ] : ['total_entries' => 0, 'total_income' => 0, 'total_expenses' => 0]
        ]);
    }
}
