<?php

namespace App\Http\Controllers;

use App\Models\CashRegisterEntry;
use App\Models\CashRegisterSession;
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

    /**
     * Get current session status for a store
     */
    public function sessionStatus(Request $request)
    {
        $storeId = $request->input('store_id');

        $query = CashRegisterSession::with(['store', 'openedByUser:id,name', 'closedByUser:id,name'])
            ->where('status', 'open');

        if ($storeId) {
            $query->where('store_id', $storeId);
        }

        $sessions = $query->get();

        // Calculate expected closing for each open session
        $sessions->each(function ($session) {
            $session->expected_closing_amount = $session->calculateExpectedClosing();
        });

        return response()->json([
            'success' => true,
            'sessions' => $sessions
        ]);
    }

    /**
     * Open a new cash register session
     */
    public function openSession(Request $request)
    {
        $validated = $request->validate([
            'store_id' => 'required|exists:stores,id',
            'opening_amount' => 'required|numeric|min:0',
            'opening_notes' => 'nullable|string|max:500',
        ]);

        // Check if there's already an open session for this store
        $existingSession = CashRegisterSession::where('store_id', $validated['store_id'])
            ->where('status', 'open')
            ->first();

        if ($existingSession) {
            return response()->json([
                'success' => false,
                'message' => 'Υπάρχει ήδη ανοιχτό ταμείο για αυτό το κατάστημα',
                'session' => $existingSession->load(['store', 'openedByUser:id,name'])
            ], 422);
        }

        $session = CashRegisterSession::create([
            'store_id' => $validated['store_id'],
            'opened_by' => auth()->id() ?? 1,
            'opening_amount' => $validated['opening_amount'],
            'opening_notes' => $validated['opening_notes'] ?? null,
            'status' => 'open',
            'opened_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Το ταμείο άνοιξε επιτυχώς',
            'session' => $session->load(['store', 'openedByUser:id,name'])
        ], 201);
    }

    /**
     * Close an open cash register session
     */
    public function closeSession(Request $request, $sessionId)
    {
        $validated = $request->validate([
            'actual_closing_amount' => 'required|numeric|min:0',
            'closing_notes' => 'nullable|string|max:500',
        ]);

        $session = CashRegisterSession::where('id', $sessionId)
            ->where('status', 'open')
            ->first();

        if (!$session) {
            return response()->json([
                'success' => false,
                'message' => 'Δεν βρέθηκε ανοιχτό ταμείο'
            ], 404);
        }

        $expectedAmount = $session->calculateExpectedClosing();
        $discrepancy = $validated['actual_closing_amount'] - $expectedAmount;

        $session->update([
            'closed_by' => auth()->id() ?? 1,
            'expected_closing_amount' => $expectedAmount,
            'actual_closing_amount' => $validated['actual_closing_amount'],
            'discrepancy' => $discrepancy,
            'closing_notes' => $validated['closing_notes'] ?? null,
            'status' => 'closed',
            'closed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Το ταμείο έκλεισε επιτυχώς',
            'session' => $session->load(['store', 'openedByUser:id,name', 'closedByUser:id,name']),
            'expected_amount' => $expectedAmount,
            'actual_amount' => $validated['actual_closing_amount'],
            'discrepancy' => $discrepancy
        ]);
    }

    /**
     * Get session history
     */
    public function sessionHistory(Request $request)
    {
        $query = CashRegisterSession::with(['store', 'openedByUser:id,name', 'closedByUser:id,name'])
            ->orderBy('opened_at', 'desc');

        if ($request->has('store_id')) {
            $query->where('store_id', $request->store_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $sessions = $query->paginate($request->get('per_page', 15));

        return response()->json($sessions);
    }
}
