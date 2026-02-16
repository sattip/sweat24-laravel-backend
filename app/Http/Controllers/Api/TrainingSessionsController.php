<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TrainingSession;
use App\Models\TrainingExercise;
use App\Models\User;
use App\Models\Booking;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class TrainingSessionsController extends Controller
{
    /**
     * Get all training sessions for a user.
     */
    public function index(Request $request, $userId = null): JsonResponse
    {
        $query = TrainingSession::with(['user', 'trainer', 'exercises.exercise']);

        if ($userId) {
            $query->forUser($userId);
        }

        // Filter by trainer
        if ($request->has('trainer_id')) {
            $query->forTrainer($request->trainer_id);
        }

        // Filter by date range
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->betweenDates($request->start_date, $request->end_date);
        }

        // Filter by session type
        if ($request->has('session_type')) {
            $query->where('session_type', $request->session_type);
        }

        // Filter by intensity
        if ($request->has('min_intensity') && $request->has('max_intensity')) {
            $query->byIntensity($request->min_intensity, $request->max_intensity);
        }

        $sessions = $query->orderBy('session_date', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $sessions
        ]);
    }

    /**
     * Store a new training session.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'trainer_id' => 'nullable|exists:users,id',
            'booking_id' => 'nullable|exists:bookings,id',
            'session_date' => 'required|date',
            'duration_minutes' => 'required|integer|min:1',
            'session_type' => 'required|in:personal,semi_personal,group',
            'intensity' => 'required|integer|min:1|max:10',
            'muscle_groups' => 'nullable|array',
            'muscle_groups.*' => 'string',
            'includes_cardio' => 'nullable|boolean',
            'includes_cognitive' => 'nullable|boolean',
            'includes_mobility' => 'nullable|boolean',
            'includes_balance' => 'nullable|boolean',
            'includes_functional' => 'nullable|boolean',
            'is_total_body' => 'nullable|boolean',
            'notes' => 'nullable|string|max:2000',
            'exercises' => 'nullable|array',
            'exercises.*.exercise_id' => 'nullable|exists:exercises,id',
            'exercises.*.exercise_name' => 'nullable|string',
            'exercises.*.sets' => 'required|integer|min:1',
            'exercises.*.reps' => 'required|integer|min:1',
            'exercises.*.weight_kg' => 'nullable|numeric|min:0',
            'exercises.*.rest_seconds' => 'nullable|integer|min:0',
            'exercises.*.tempo' => 'nullable|string',
            'exercises.*.rir' => 'nullable|integer|min:0',
            'exercises.*.exercise_type' => 'nullable|in:standard,superset,drop,pyramid,emom,amrap,failure,timed',
            'exercises.*.superset_with' => 'nullable|string',
            'exercises.*.notes' => 'nullable|string',
            'exercises.*.order' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Create the training session
            $session = TrainingSession::create([
                'user_id' => $request->user_id,
                'trainer_id' => $request->trainer_id ?? auth()->id(),
                'booking_id' => $request->booking_id,
                'session_date' => $request->session_date,
                'duration_minutes' => $request->duration_minutes,
                'session_type' => $request->session_type,
                'intensity' => $request->intensity,
                'muscle_groups' => $request->muscle_groups ?? [],
                'includes_cardio' => $request->includes_cardio ?? false,
                'includes_cognitive' => $request->includes_cognitive ?? false,
                'includes_mobility' => $request->includes_mobility ?? false,
                'includes_balance' => $request->includes_balance ?? false,
                'includes_functional' => $request->includes_functional ?? false,
                'is_total_body' => $request->is_total_body ?? false,
                'notes' => $request->notes,
            ]);

            // Create the exercises if provided
            if ($request->has('exercises') && is_array($request->exercises)) {
                foreach ($request->exercises as $index => $exerciseData) {
                    TrainingExercise::create([
                        'training_session_id' => $session->id,
                        'exercise_id' => $exerciseData['exercise_id'] ?? null,
                        'exercise_name' => $exerciseData['exercise_name'] ?? null,
                        'sets' => $exerciseData['sets'],
                        'reps' => $exerciseData['reps'],
                        'weight_kg' => $exerciseData['weight_kg'] ?? null,
                        'rest_seconds' => $exerciseData['rest_seconds'] ?? null,
                        'tempo' => $exerciseData['tempo'] ?? null,
                        'rir' => $exerciseData['rir'] ?? null,
                        'exercise_type' => $exerciseData['exercise_type'] ?? 'standard',
                        'superset_with' => $exerciseData['superset_with'] ?? null,
                        'notes' => $exerciseData['notes'] ?? null,
                        'order' => $exerciseData['order'] ?? $index,
                    ]);
                }

                // Calculate total volume
                $session->calculateTotalVolume();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Training session created successfully',
                'data' => $session->load(['user', 'trainer', 'exercises.exercise'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create training session',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified training session.
     */
    public function show($id): JsonResponse
    {
        $session = TrainingSession::with(['user', 'trainer', 'exercises.exercise', 'booking'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $session
        ]);
    }

    /**
     * Update the specified training session.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $session = TrainingSession::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'session_date' => 'sometimes|required|date',
            'duration_minutes' => 'sometimes|required|integer|min:1',
            'session_type' => 'sometimes|required|in:personal,semi_personal,group',
            'intensity' => 'sometimes|required|integer|min:1|max:10',
            'muscle_groups' => 'nullable|array',
            'muscle_groups.*' => 'string',
            'includes_cardio' => 'nullable|boolean',
            'includes_cognitive' => 'nullable|boolean',
            'includes_mobility' => 'nullable|boolean',
            'includes_balance' => 'nullable|boolean',
            'includes_functional' => 'nullable|boolean',
            'is_total_body' => 'nullable|boolean',
            'notes' => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $session->update($request->only([
            'session_date', 'duration_minutes', 'session_type', 'intensity',
            'muscle_groups', 'includes_cardio', 'includes_cognitive',
            'includes_mobility', 'includes_balance', 'includes_functional',
            'is_total_body', 'notes'
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Training session updated successfully',
            'data' => $session->load(['user', 'trainer', 'exercises.exercise'])
        ]);
    }

    /**
     * Remove the specified training session.
     */
    public function destroy($id): JsonResponse
    {
        $session = TrainingSession::findOrFail($id);
        $session->delete(); // This will cascade delete the exercises

        return response()->json([
            'success' => true,
            'message' => 'Training session deleted successfully'
        ]);
    }

    /**
     * Get training analytics for a user.
     */
    public function analytics(Request $request, $userId): JsonResponse
    {
        $user = User::findOrFail($userId);

        $startDate = $request->get('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));

        $sessions = TrainingSession::forUser($userId)
            ->betweenDates($startDate, $endDate)
            ->with('exercises')
            ->get();

        // Calculate analytics
        $totalSessions = $sessions->count();
        $totalVolume = $sessions->sum('total_volume');
        $avgIntensity = $sessions->avg('intensity');

        // Muscle group frequency
        $muscleGroupCounts = [];
        foreach ($sessions as $session) {
            if (is_array($session->muscle_groups)) {
                foreach ($session->muscle_groups as $group) {
                    $muscleGroupCounts[$group] = ($muscleGroupCounts[$group] ?? 0) + 1;
                }
            }
        }

        // Calculate muscle balance percentage
        $muscleBalance = [];
        if ($totalSessions > 0) {
            foreach ($muscleGroupCounts as $group => $count) {
                $muscleBalance[$group] = round(($count / $totalSessions) * 100, 1);
            }
        }

        // Weekly trends
        $weeklyVolume = $sessions->groupBy(function ($session) {
            return $session->session_date->format('Y-W');
        })->map->sum('total_volume');

        $analytics = [
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'totals' => [
                'sessions' => $totalSessions,
                'total_volume_kg' => round($totalVolume, 2),
                'avg_intensity' => round($avgIntensity, 1),
                'avg_duration' => round($sessions->avg('duration_minutes'), 0),
            ],
            'muscle_groups' => [
                'frequency' => $muscleGroupCounts,
                'balance_percentage' => $muscleBalance,
            ],
            'weekly_volume' => $weeklyVolume,
            'intensity_distribution' => [
                'low' => $sessions->filter(fn($s) => $s->intensity <= 3)->count(),
                'moderate' => $sessions->filter(fn($s) => $s->intensity > 3 && $s->intensity <= 6)->count(),
                'high' => $sessions->filter(fn($s) => $s->intensity > 6)->count(),
            ],
            'session_types' => $sessions->groupBy('session_type')->map->count(),
            'includes' => [
                'cardio' => $sessions->where('includes_cardio', true)->count(),
                'cognitive' => $sessions->where('includes_cognitive', true)->count(),
                'mobility' => $sessions->where('includes_mobility', true)->count(),
                'balance' => $sessions->where('includes_balance', true)->count(),
                'functional' => $sessions->where('includes_functional', true)->count(),
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $analytics
        ]);
    }

    /**
     * Get enhanced training analytics with adherence, alerts, and MoM.
     */
    public function enhancedAnalytics(Request $request, $userId): JsonResponse
    {
        $user = User::findOrFail($userId);

        $startDate = $request->get('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));

        $sessions = TrainingSession::forUser($userId)
            ->betweenDates($startDate, $endDate)
            ->with('exercises')
            ->get();

        // Basic analytics
        $totalSessions = $sessions->count();
        $totalVolume = $sessions->sum('total_volume');
        $avgIntensity = $sessions->avg('intensity');

        // Muscle group frequency
        $muscleGroupCounts = [];
        foreach ($sessions as $session) {
            if (is_array($session->muscle_groups)) {
                foreach ($session->muscle_groups as $group) {
                    $muscleGroupCounts[$group] = ($muscleGroupCounts[$group] ?? 0) + 1;
                }
            }
        }

        // Calculate muscle balance percentage
        $muscleBalance = [];
        if ($totalSessions > 0) {
            foreach ($muscleGroupCounts as $group => $count) {
                $muscleBalance[$group] = round(($count / $totalSessions) * 100, 1);
            }
        }

        // Weekly trends with WoW comparison
        $weeklyData = $this->calculateWeeklyTrends($sessions);

        // Monthly trends with MoM comparison
        $monthlyData = $this->calculateMonthlyTrends($userId, $startDate, $endDate);

        // Adherence tracking
        $adherenceData = $this->calculateAdherence($userId, $startDate, $endDate);

        // Load management alerts
        $alerts = $this->generateAlerts($userId, $sessions, $weeklyData, $adherenceData);

        $analytics = [
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'totals' => [
                'sessions' => $totalSessions,
                'total_volume_kg' => round($totalVolume, 2),
                'avg_intensity' => round($avgIntensity ?? 0, 1),
                'avg_duration' => round($sessions->avg('duration_minutes') ?? 0, 0),
            ],
            'muscle_groups' => [
                'frequency' => $muscleGroupCounts,
                'balance_percentage' => $muscleBalance,
            ],
            'weekly_trends' => $weeklyData,
            'monthly_trends' => $monthlyData,
            'adherence' => $adherenceData,
            'alerts' => $alerts,
            'intensity_distribution' => [
                'low' => $sessions->filter(fn($s) => $s->intensity <= 3)->count(),
                'moderate' => $sessions->filter(fn($s) => $s->intensity > 3 && $s->intensity <= 6)->count(),
                'high' => $sessions->filter(fn($s) => $s->intensity > 6)->count(),
            ],
            'session_types' => $sessions->groupBy('session_type')->map->count(),
            'includes' => [
                'cardio' => $sessions->where('includes_cardio', true)->count(),
                'cognitive' => $sessions->where('includes_cognitive', true)->count(),
                'mobility' => $sessions->where('includes_mobility', true)->count(),
                'balance' => $sessions->where('includes_balance', true)->count(),
                'functional' => $sessions->where('includes_functional', true)->count(),
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $analytics
        ]);
    }

    /**
     * Calculate weekly trends with WoW comparison.
     */
    private function calculateWeeklyTrends($sessions)
    {
        $weeklyVolume = $sessions->groupBy(function ($session) {
            return $session->session_date->format('Y-W');
        });

        $weeks = [];
        $previousVolume = null;
        $previousSessions = null;
        $previousIntensity = null;

        foreach ($weeklyVolume as $week => $weekSessions) {
            $volume = $weekSessions->sum('total_volume');
            $count = $weekSessions->count();
            $intensity = $weekSessions->avg('intensity');

            $volumeChange = null;
            $sessionsChange = null;
            $intensityChange = null;

            if ($previousVolume !== null && $previousVolume > 0) {
                $volumeChange = round((($volume - $previousVolume) / $previousVolume) * 100, 1);
            }
            if ($previousSessions !== null && $previousSessions > 0) {
                $sessionsChange = round((($count - $previousSessions) / $previousSessions) * 100, 1);
            }
            if ($previousIntensity !== null && $previousIntensity > 0) {
                $intensityChange = round((($intensity - $previousIntensity) / $previousIntensity) * 100, 1);
            }

            $weeks[$week] = [
                'volume' => round($volume, 2),
                'sessions' => $count,
                'avg_intensity' => round($intensity ?? 0, 1),
                'wow_volume_change' => $volumeChange,
                'wow_sessions_change' => $sessionsChange,
                'wow_intensity_change' => $intensityChange,
            ];

            $previousVolume = $volume;
            $previousSessions = $count;
            $previousIntensity = $intensity;
        }

        return $weeks;
    }

    /**
     * Calculate monthly trends with MoM comparison.
     */
    private function calculateMonthlyTrends($userId, $startDate, $endDate)
    {
        // Get sessions for the last 3 months to have comparison data
        $extendedStartDate = Carbon::parse($startDate)->subMonths(2)->format('Y-m-d');

        $sessions = TrainingSession::forUser($userId)
            ->betweenDates($extendedStartDate, $endDate)
            ->get();

        $monthlyData = $sessions->groupBy(function ($session) {
            return $session->session_date->format('Y-m');
        });

        $months = [];
        $previousVolume = null;
        $previousSessions = null;

        // Calculate muscle group trends per month
        foreach ($monthlyData as $month => $monthSessions) {
            $volume = $monthSessions->sum('total_volume');
            $count = $monthSessions->count();
            $intensity = $monthSessions->avg('intensity');

            // Muscle group frequency for this month
            $muscleGroupCounts = [];
            foreach ($monthSessions as $session) {
                if (is_array($session->muscle_groups)) {
                    foreach ($session->muscle_groups as $group) {
                        $muscleGroupCounts[$group] = ($muscleGroupCounts[$group] ?? 0) + 1;
                    }
                }
            }

            $volumeChange = null;
            $sessionsChange = null;

            if ($previousVolume !== null && $previousVolume > 0) {
                $volumeChange = round((($volume - $previousVolume) / $previousVolume) * 100, 1);
            }
            if ($previousSessions !== null && $previousSessions > 0) {
                $sessionsChange = round((($count - $previousSessions) / $previousSessions) * 100, 1);
            }

            $months[$month] = [
                'volume' => round($volume, 2),
                'sessions' => $count,
                'avg_intensity' => round($intensity ?? 0, 1),
                'mom_volume_change' => $volumeChange,
                'mom_sessions_change' => $sessionsChange,
                'muscle_groups' => $muscleGroupCounts,
            ];

            $previousVolume = $volume;
            $previousSessions = $count;
        }

        return $months;
    }

    /**
     * Calculate adherence based on bookings.
     */
    private function calculateAdherence($userId, $startDate, $endDate)
    {
        // Get all bookings for the user in the period
        $bookings = Booking::where('user_id', $userId)
            ->whereBetween('date', [$startDate, $endDate])
            ->whereIn('booking_type', ['personal', 'semi_personal', 'group'])
            ->get();

        $totalScheduled = $bookings->count();
        $attended = $bookings->where('attended', true)->count();
        $missed = $bookings->where('attended', false)
            ->whereIn('status', ['absent', 'no_show', 'cancelled_late'])
            ->count();

        $adherencePercentage = $totalScheduled > 0
            ? round(($attended / $totalScheduled) * 100, 1)
            : 0;

        // Get missed session details
        $missedSessions = $bookings->filter(function ($booking) {
            return !$booking->attended && in_array($booking->status, ['absent', 'no_show', 'cancelled_late']);
        })->map(function ($booking) {
            return [
                'date' => $booking->date->format('Y-m-d'),
                'class_name' => $booking->class_name,
                'reason' => $booking->absence_reason ?? 'Χωρίς αιτιολογία',
            ];
        })->values();

        return [
            'total_scheduled' => $totalScheduled,
            'attended' => $attended,
            'missed' => $missed,
            'adherence_percentage' => $adherencePercentage,
            'missed_sessions' => $missedSessions,
        ];
    }

    /**
     * Generate load management alerts.
     */
    private function generateAlerts($userId, $sessions, $weeklyData, $adherenceData)
    {
        $alerts = [];

        // Get the last two weeks' data for comparison
        $weeks = array_keys($weeklyData);
        if (count($weeks) >= 2) {
            $currentWeek = end($weeklyData);
            $previousWeek = prev($weeklyData);

            // RPE/Intensity Alert: >15% increase
            if ($currentWeek['wow_intensity_change'] !== null && $currentWeek['wow_intensity_change'] > 15) {
                $alerts[] = [
                    'type' => 'high_intensity',
                    'severity' => 'warning',
                    'message' => "Υψηλή αύξηση έντασης (+{$currentWeek['wow_intensity_change']}%). Πρότεινε αποφόρτιση.",
                    'icon' => '⚠️',
                ];
            }

            // Volume Alert: >10% increase
            if ($currentWeek['wow_volume_change'] !== null && $currentWeek['wow_volume_change'] > 10) {
                $alerts[] = [
                    'type' => 'high_volume',
                    'severity' => 'warning',
                    'message' => "Υψηλή φόρτιση (+{$currentWeek['wow_volume_change']}% όγκος). Πρότεινε recovery day.",
                    'icon' => '⚠️',
                ];
            }

            // Session decrease alert: >30% drop
            if ($currentWeek['wow_sessions_change'] !== null && $currentWeek['wow_sessions_change'] < -30) {
                $alerts[] = [
                    'type' => 'engagement_drop',
                    'severity' => 'danger',
                    'message' => "Απότομη πτώση προπονήσεων ({$currentWeek['wow_sessions_change']}%). Πρότεινε follow-up.",
                    'icon' => '🚨',
                ];
            }
        }

        // Adherence Alert: <70%
        if ($adherenceData['adherence_percentage'] < 70 && $adherenceData['total_scheduled'] > 0) {
            $alerts[] = [
                'type' => 'low_adherence',
                'severity' => 'danger',
                'message' => "Χαμηλή συνέπεια ({$adherenceData['adherence_percentage']}%). Επικοινώνησε με τον πελάτη.",
                'icon' => '🚨',
            ];
        }

        // Good performance alerts
        if (empty($alerts) && $sessions->count() > 0) {
            $avgIntensity = $sessions->avg('intensity');
            if ($avgIntensity >= 6 && $avgIntensity <= 8) {
                $alerts[] = [
                    'type' => 'good_intensity',
                    'severity' => 'success',
                    'message' => "Καλή ένταση προπόνησης ({$avgIntensity}/10).",
                    'icon' => '✅',
                ];
            }
        }

        return $alerts;
    }

    /**
     * Get load management alerts for a user.
     */
    public function alerts(Request $request, $userId): JsonResponse
    {
        $user = User::findOrFail($userId);

        $startDate = $request->get('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));

        $sessions = TrainingSession::forUser($userId)
            ->betweenDates($startDate, $endDate)
            ->with('exercises')
            ->get();

        $weeklyData = $this->calculateWeeklyTrends($sessions);
        $adherenceData = $this->calculateAdherence($userId, $startDate, $endDate);
        $alerts = $this->generateAlerts($userId, $sessions, $weeklyData, $adherenceData);

        return response()->json([
            'success' => true,
            'data' => [
                'alerts' => $alerts,
                'adherence' => $adherenceData,
            ]
        ]);
    }
}
