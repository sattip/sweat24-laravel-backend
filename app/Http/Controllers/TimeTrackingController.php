<?php

namespace App\Http\Controllers;

use App\Models\WorkTimeEntry;
use Illuminate\Http\Request;
use Carbon\Carbon;

class TimeTrackingController extends Controller
{
    /**
     * Start a new work session
     */
    public function startSession(Request $request)
    {
        // Check if there's already an active session
        $activeSession = WorkTimeEntry::where('instructor_id', auth()->id())
            ->whereNull('end_time')
            ->first();
            
        if ($activeSession) {
            return response()->json([
                'success' => false,
                'message' => 'Υπάρχει ήδη ενεργή συνεδρία. Παρακαλώ τερματίστε την πρώτα.',
                'active_session' => $activeSession
            ], 400);
        }
        
        $session = WorkTimeEntry::create([
            'instructor_id' => auth()->id(),
            'date' => today(),
            'start_time' => now(),
            'status' => 'in_progress'
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Η συνεδρία ξεκίνησε επιτυχώς',
            'session' => $session
        ]);
    }
    
    /**
     * End the current work session
     */
    public function endSession(Request $request)
    {
        $activeSession = WorkTimeEntry::where('instructor_id', auth()->id())
            ->whereNull('end_time')
            ->first();
            
        if (!$activeSession) {
            return response()->json([
                'success' => false,
                'message' => 'Δεν υπάρχει ενεργή συνεδρία'
            ], 404);
        }
        
        $activeSession->update([
            'end_time' => now(),
            'status' => 'completed',
            'duration' => now()->diffInMinutes($activeSession->start_time)
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Η συνεδρία ολοκληρώθηκε επιτυχώς',
            'session' => $activeSession
        ]);
    }
    
    /**
     * Get current active session
     */
    public function currentSession()
    {
        $activeSession = WorkTimeEntry::where('instructor_id', auth()->id())
            ->whereNull('end_time')
            ->first();
            
        return response()->json([
            'active' => $activeSession ? true : false,
            'session' => $activeSession
        ]);
    }
    
    /**
     * Get work history
     */
    public function history(Request $request)
    {
        $query = WorkTimeEntry::where('instructor_id', auth()->id());
        
        if ($request->has('date_from')) {
            $query->whereDate('date', '>=', $request->date_from);
        }
        
        if ($request->has('date_to')) {
            $query->whereDate('date', '<=', $request->date_to);
        }
        
        $entries = $query->orderBy('date', 'desc')->paginate(20);
        
        return response()->json($entries);
    }
    
    /**
     * Admin: Edit work time entry
     */
    public function adminUpdate(Request $request, WorkTimeEntry $entry)
    {
        if (!auth()->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $validated = $request->validate([
            'start_time' => 'required|date',
            'end_time' => 'nullable|date|after:start_time',
            'notes' => 'nullable|string'
        ]);
        
        if ($validated['end_time']) {
            $validated['duration'] = Carbon::parse($validated['end_time'])->diffInMinutes($validated['start_time']);
            $validated['status'] = 'completed';
        }
        
        $entry->update($validated);
        
        return response()->json([
            'success' => true,
            'message' => 'Η εγγραφή ενημερώθηκε επιτυχώς',
            'entry' => $entry
        ]);
    }
    
    /**
     * Admin: Get all work time entries
     */
    public function adminIndex(Request $request)
    {
        if (!auth()->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $query = WorkTimeEntry::with('instructor');
        
        if ($request->has('instructor_id')) {
            $query->where('instructor_id', $request->instructor_id);
        }
        
        if ($request->has('date_from')) {
            $query->whereDate('date', '>=', $request->date_from);
        }
        
        if ($request->has('date_to')) {
            $query->whereDate('date', '<=', $request->date_to);
        }
        
        $entries = $query->orderBy('date', 'desc')->paginate(20);
        
        return response()->json($entries);
    }
}