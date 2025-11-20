<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WorkSession;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;

class WorkSessionController extends Controller
{
    /**
     * Get current user's work sessions.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = WorkSession::where('user_id', $user->id)
            ->orderBy('clock_in', 'desc');

        // Filter by date
        if ($request->has('date')) {
            $query->onDate($request->date);
        }

        // Filter by date range
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->betweenDates(
                $request->start_date . ' 00:00:00',
                $request->end_date . ' 23:59:59'
            );
        }

        $sessions = $query->get();

        // Calculate total hours
        $totalHours = $sessions->sum('hours_worked');

        return response()->json([
            'success' => true,
            'data' => $sessions,
            'total_hours' => round($totalHours, 2)
        ]);
    }

    /**
     * Get current active session.
     */
    public function getCurrentSession(Request $request): JsonResponse
    {
        $user = $request->user();

        $activeSession = WorkSession::where('user_id', $user->id)
            ->active()
            ->first();

        return response()->json([
            'success' => true,
            'data' => $activeSession,
            'is_clocked_in' => $activeSession !== null
        ]);
    }

    /**
     * Clock in - start a new work session.
     */
    public function clockIn(Request $request): JsonResponse
    {
        $user = $request->user();

        // Check if already clocked in
        $activeSession = WorkSession::where('user_id', $user->id)
            ->active()
            ->first();

        if ($activeSession) {
            return response()->json([
                'success' => false,
                'message' => 'You are already clocked in',
                'data' => $activeSession
            ], 400);
        }

        $session = WorkSession::create([
            'user_id' => $user->id,
            'clock_in' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Clocked in successfully',
            'data' => $session
        ], 201);
    }

    /**
     * Clock out - end current work session.
     */
    public function clockOut(Request $request): JsonResponse
    {
        $user = $request->user();

        $activeSession = WorkSession::where('user_id', $user->id)
            ->active()
            ->first();

        if (!$activeSession) {
            return response()->json([
                'success' => false,
                'message' => 'You are not clocked in'
            ], 400);
        }

        $clockOut = now();
        $hoursWorked = $activeSession->calculateHoursWorked() ??
            round(Carbon::parse($activeSession->clock_in)->diffInMinutes($clockOut) / 60, 2);

        $activeSession->update([
            'clock_out' => $clockOut,
            'hours_worked' => $hoursWorked,
            'notes' => $request->notes ?? $activeSession->notes,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Clocked out successfully',
            'data' => $activeSession->fresh()
        ]);
    }

    /**
     * Update a work session (trainer can edit their own sessions).
     */
    public function update(Request $request, $id): JsonResponse
    {
        $user = $request->user();

        $session = WorkSession::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$session) {
            return response()->json([
                'success' => false,
                'message' => 'Session not found'
            ], 404);
        }

        $validated = $request->validate([
            'clock_in' => 'sometimes|required|date',
            'clock_out' => 'nullable|date|after:clock_in',
            'notes' => 'nullable|string|max:500',
        ]);

        // Update session
        $session->update($validated);

        // Recalculate hours if both times are set
        if ($session->clock_in && $session->clock_out) {
            $session->hours_worked = $session->calculateHoursWorked();
            $session->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Session updated successfully',
            'data' => $session->fresh()
        ]);
    }

    /**
     * Delete a work session.
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        $user = $request->user();

        $session = WorkSession::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$session) {
            return response()->json([
                'success' => false,
                'message' => 'Session not found'
            ], 404);
        }

        $session->delete();

        return response()->json([
            'success' => true,
            'message' => 'Session deleted successfully'
        ]);
    }

    /**
     * Get today's summary for current user.
     */
    public function todaySummary(Request $request): JsonResponse
    {
        $user = $request->user();

        $sessions = WorkSession::where('user_id', $user->id)
            ->onDate(now()->toDateString())
            ->get();

        $totalHours = $sessions->sum('hours_worked');
        $activeSession = $sessions->firstWhere('clock_out', null);

        return response()->json([
            'success' => true,
            'data' => [
                'sessions' => $sessions,
                'total_hours' => round($totalHours, 2),
                'session_count' => $sessions->count(),
                'is_clocked_in' => $activeSession !== null,
                'current_session' => $activeSession
            ]
        ]);
    }

    // ========== Admin Methods ==========

    /**
     * Get all trainers' work sessions (admin only).
     */
    public function adminIndex(Request $request): JsonResponse
    {
        $query = WorkSession::with('user:id,name,email')
            ->orderBy('clock_in', 'desc');

        // Filter by user
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by date
        if ($request->has('date')) {
            $query->onDate($request->date);
        }

        // Filter by date range
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->betweenDates(
                $request->start_date . ' 00:00:00',
                $request->end_date . ' 23:59:59'
            );
        }

        $sessions = $query->get();

        return response()->json([
            'success' => true,
            'data' => $sessions
        ]);
    }

    /**
     * Get trainer hours summary (admin only).
     */
    public function adminSummary(Request $request): JsonResponse
    {
        // Get trainers
        $trainers = User::where('role', 'trainer')
            ->orWhere('role', 'admin')
            ->get(['id', 'name', 'email']);

        // Date range - default to current month
        $startDate = $request->start_date ?? now()->startOfMonth()->toDateString();
        $endDate = $request->end_date ?? now()->endOfMonth()->toDateString();

        $summary = [];

        foreach ($trainers as $trainer) {
            $sessions = WorkSession::where('user_id', $trainer->id)
                ->betweenDates($startDate . ' 00:00:00', $endDate . ' 23:59:59')
                ->completed()
                ->get();

            $totalHours = $sessions->sum('hours_worked');
            $sessionCount = $sessions->count();

            // Get active session if any
            $activeSession = WorkSession::where('user_id', $trainer->id)
                ->active()
                ->first();

            $summary[] = [
                'user_id' => $trainer->id,
                'name' => $trainer->name,
                'email' => $trainer->email,
                'total_hours' => round($totalHours, 2),
                'session_count' => $sessionCount,
                'is_currently_working' => $activeSession !== null,
                'current_session_start' => $activeSession ? $activeSession->clock_in : null,
            ];
        }

        // Sort by total hours descending
        usort($summary, function ($a, $b) {
            return $b['total_hours'] <=> $a['total_hours'];
        });

        return response()->json([
            'success' => true,
            'data' => $summary,
            'date_range' => [
                'start' => $startDate,
                'end' => $endDate
            ]
        ]);
    }

    /**
     * Get detailed hours for a specific trainer (admin only).
     */
    public function adminTrainerDetail(Request $request, $userId): JsonResponse
    {
        $trainer = User::findOrFail($userId);

        // Date range - default to current month
        $startDate = $request->start_date ?? now()->startOfMonth()->toDateString();
        $endDate = $request->end_date ?? now()->endOfMonth()->toDateString();

        $sessions = WorkSession::where('user_id', $userId)
            ->betweenDates($startDate . ' 00:00:00', $endDate . ' 23:59:59')
            ->orderBy('clock_in', 'desc')
            ->get();

        // Group by date
        $byDate = $sessions->groupBy(function ($session) {
            return Carbon::parse($session->clock_in)->toDateString();
        })->map(function ($daySessions) {
            return [
                'sessions' => $daySessions,
                'total_hours' => round($daySessions->sum('hours_worked'), 2),
                'session_count' => $daySessions->count()
            ];
        });

        $totalHours = $sessions->sum('hours_worked');

        return response()->json([
            'success' => true,
            'data' => [
                'trainer' => [
                    'id' => $trainer->id,
                    'name' => $trainer->name,
                    'email' => $trainer->email
                ],
                'sessions' => $sessions,
                'by_date' => $byDate,
                'total_hours' => round($totalHours, 2),
                'date_range' => [
                    'start' => $startDate,
                    'end' => $endDate
                ]
            ]
        ]);
    }

    /**
     * Admin can update any session.
     */
    public function adminUpdate(Request $request, $id): JsonResponse
    {
        $session = WorkSession::findOrFail($id);

        $validated = $request->validate([
            'clock_in' => 'sometimes|required|date',
            'clock_out' => 'nullable|date|after:clock_in',
            'notes' => 'nullable|string|max:500',
        ]);

        $session->update($validated);

        // Recalculate hours if both times are set
        if ($session->clock_in && $session->clock_out) {
            $session->hours_worked = $session->calculateHoursWorked();
            $session->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Session updated successfully',
            'data' => $session->fresh()->load('user:id,name,email')
        ]);
    }

    /**
     * Admin can delete any session.
     */
    public function adminDestroy($id): JsonResponse
    {
        $session = WorkSession::findOrFail($id);
        $session->delete();

        return response()->json([
            'success' => true,
            'message' => 'Session deleted successfully'
        ]);
    }
}
