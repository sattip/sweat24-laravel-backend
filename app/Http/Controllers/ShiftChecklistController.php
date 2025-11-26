<?php

namespace App\Http\Controllers;

use App\Models\ShiftChecklist;
use App\Models\WorkSession;
use Illuminate\Http\Request;

class ShiftChecklistController extends Controller
{
    /**
     * Display a listing of shift checklists
     */
    public function index(Request $request)
    {
        $query = ShiftChecklist::with(['user:id,name,email', 'store', 'workSession']);

        if ($request->has('store_id')) {
            $query->where('store_id', $request->store_id);
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('date_from')) {
            $query->whereDate('completed_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('completed_at', '<=', $request->date_to);
        }

        // Filter by issues
        \Log::info('ShiftChecklist filter params', [
            'has_issues_raw' => $request->input('has_issues'),
            'has_issues_filled' => $request->filled('has_issues'),
            'all_params' => $request->all()
        ]);

        if ($request->filled('has_issues')) {
            \Log::info('Applying has_issues filter: ' . $request->has_issues);
            if ($request->has_issues === 'yes') {
                $query->where(function ($q) {
                    $q->where('equipment_checked', 'no')
                      ->orWhere('area_tidy', 'no')
                      ->orWhereNotNull('issues_reported');
                });
            } elseif ($request->has_issues === 'no') {
                \Log::info('Filtering for NO issues');
                $query->where(function ($q) {
                    $q->where(function ($inner) {
                        $inner->where('equipment_checked', '!=', 'no')
                              ->orWhereNull('equipment_checked');
                    })
                    ->where(function ($inner) {
                        $inner->where('area_tidy', '!=', 'no')
                              ->orWhereNull('area_tidy');
                    })
                    ->whereNull('issues_reported');
                });
            }
        }

        $checklists = $query->orderBy('completed_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json($checklists);
    }

    /**
     * Store a new shift checklist
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'store_id' => 'required|exists:stores,id',
            'work_session_id' => 'nullable|exists:work_sessions,id',
            'type' => 'required|in:opening,closing',
            // Cash register
            'cash_counted' => 'required|in:yes,no,na',
            'cash_amount' => 'nullable|numeric|min:0',
            // Inventory
            'towels_checked' => 'required|in:yes,no,na',
            'towels_count' => 'nullable|integer|min:0',
            'water_checked' => 'required|in:yes,no,na',
            'water_count' => 'nullable|integer|min:0',
            // Equipment
            'equipment_checked' => 'required|in:yes,no,na',
            'equipment_notes' => 'nullable|string|max:500',
            // Cleanliness
            'area_tidy' => 'required|in:yes,no,na',
            'locker_rooms_checked' => 'nullable|in:yes,no,na',
            'showers_checked' => 'nullable|in:yes,no,na',
            // Security (for closing)
            'doors_locked' => 'nullable|in:yes,no,na',
            'lights_off' => 'nullable|in:yes,no,na',
            'ac_off' => 'nullable|in:yes,no,na',
            'alarm_set' => 'nullable|in:yes,no,na',
            // Notes
            'notes' => 'nullable|string|max:1000',
            'issues_reported' => 'nullable|string|max:1000',
        ]);

        $validated['user_id'] = auth()->id() ?? 1;
        $validated['completed_at'] = now();

        $checklist = ShiftChecklist::create($validated);

        // If work_session_id is provided, we can link it (optional enhancement)
        // Currently we just store the reference in the checklist itself

        return response()->json([
            'success' => true,
            'message' => $validated['type'] === 'opening'
                ? 'Το ερωτηματολόγιο ανοίγματος καταχωρήθηκε'
                : 'Το ερωτηματολόγιο κλεισίματος καταχωρήθηκε',
            'checklist' => $checklist->load(['user:id,name,email', 'store'])
        ], 201);
    }

    /**
     * Display a specific checklist
     */
    public function show(ShiftChecklist $shiftChecklist)
    {
        return response()->json([
            'success' => true,
            'checklist' => $shiftChecklist->load(['user:id,name,email', 'store', 'workSession'])
        ]);
    }

    /**
     * Check if checklist is needed for a work session
     */
    public function checkRequired(Request $request)
    {
        $type = $request->input('type'); // 'opening' or 'closing'
        $workSessionId = $request->input('work_session_id');

        if (!$type || !$workSessionId) {
            return response()->json([
                'required' => false
            ]);
        }

        $workSession = WorkSession::find($workSessionId);
        if (!$workSession) {
            return response()->json([
                'required' => false
            ]);
        }

        // Check if a checklist already exists for this work session and type
        $existingChecklist = ShiftChecklist::where('work_session_id', $workSessionId)
            ->where('type', $type)
            ->first();

        return response()->json([
            'required' => !$existingChecklist,
            'existing_checklist' => $existingChecklist,
            'work_session' => $workSession->load('store')
        ]);
    }

    /**
     * Get checklist statistics
     */
    public function statistics(Request $request)
    {
        $query = ShiftChecklist::query();

        if ($request->has('store_id')) {
            $query->where('store_id', $request->store_id);
        }

        if ($request->has('date_from')) {
            $query->whereDate('completed_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('completed_at', '<=', $request->date_to);
        }

        $stats = [
            'total' => $query->count(),
            'opening' => (clone $query)->where('type', 'opening')->count(),
            'closing' => (clone $query)->where('type', 'closing')->count(),
            'issues_reported' => (clone $query)->whereNotNull('issues_reported')->count(),
            'equipment_issues' => (clone $query)->where('equipment_checked', 'no')->count(),
        ];

        return response()->json([
            'success' => true,
            'statistics' => $stats
        ]);
    }

    /**
     * Get recent checklists for a user
     */
    public function userChecklists(Request $request)
    {
        $userId = $request->input('user_id', auth()->id() ?? 1);

        $checklists = ShiftChecklist::with(['store', 'workSession'])
            ->where('user_id', $userId)
            ->orderBy('completed_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'checklists' => $checklists
        ]);
    }
}
