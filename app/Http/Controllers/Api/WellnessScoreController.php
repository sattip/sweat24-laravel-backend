<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WellnessScore;
use App\Models\WellnessThreshold;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WellnessScoreController extends Controller
{
    /**
     * Submit daily wellness check (for mobile app / client).
     */
    public function submit(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'sleep_hours' => 'nullable|numeric|min:0|max:24',
            'sleep_quality' => 'nullable|in:poor,fair,good,excellent',
            'hydration_liters' => 'nullable|numeric|min:0|max:10',
            'calories_consumed' => 'nullable|integer|min:0|max:10000',
            'tdee' => 'nullable|integer|min:0|max:10000',
            'energy_level' => 'nullable|integer|min:1|max:10',
            'mood_level' => 'nullable|integer|min:1|max:10',
            'stress_level' => 'nullable|integer|min:1|max:10',
            'soreness_level' => 'nullable|integer|min:1|max:10',
            'hrv' => 'nullable|integer|min:0|max:300',
            'notes' => 'nullable|string|max:1000',
        ]);

        $userId = $validated['user_id'];

        // Check if already submitted today
        $existing = WellnessScore::where('user_id', $userId)
            ->where('date', today())
            ->first();

        if ($existing) {
            // Update existing entry
            $wellness = $existing;
        } else {
            // Create new entry
            $wellness = new WellnessScore();
            $wellness->user_id = $userId;
            $wellness->date = today();
        }

        // Set values
        $wellness->fill([
            'sleep_hours' => $validated['sleep_hours'] ?? null,
            'sleep_quality' => $validated['sleep_quality'] ?? null,
            'hydration_liters' => $validated['hydration_liters'] ?? null,
            'calories_consumed' => $validated['calories_consumed'] ?? null,
            'tdee' => $validated['tdee'] ?? null,
            'energy_level' => $validated['energy_level'] ?? null,
            'mood_level' => $validated['mood_level'] ?? null,
            'stress_level' => $validated['stress_level'] ?? null,
            'soreness_level' => $validated['soreness_level'] ?? null,
            'hrv' => $validated['hrv'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        // Calculate calories percentage if both values provided
        if ($wellness->calories_consumed && $wellness->tdee) {
            $wellness->calories_percentage = ($wellness->calories_consumed / $wellness->tdee) * 100;
        }

        // Calculate alerts and score
        $wellness->calculateAlerts();
        $wellness->calculateWellnessScore();

        $wellness->save();

        return response()->json([
            'success' => true,
            'message' => $existing ? 'Wellness score ενημερώθηκε' : 'Wellness score καταχωρήθηκε',
            'data' => $this->formatWellnessResponse($wellness),
        ]);
    }

    /**
     * Get today's wellness score for a user.
     */
    public function getToday(Request $request): JsonResponse
    {
        $userId = $request->input('user_id') ?? $request->user()?->id;

        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'User ID required',
            ], 400);
        }

        $wellness = WellnessScore::getTodayScore($userId);

        if (!$wellness) {
            return response()->json([
                'success' => true,
                'data' => null,
                'message' => 'Δεν έχει καταχωρηθεί wellness score σήμερα',
                'can_submit' => true,
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $this->formatWellnessResponse($wellness),
            'can_submit' => true, // Allow updates
        ]);
    }

    /**
     * Get wellness history for a user.
     */
    public function getHistory(Request $request): JsonResponse
    {
        $userId = $request->input('user_id') ?? $request->user()?->id;
        $days = $request->input('days', 30);

        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'User ID required',
            ], 400);
        }

        $history = WellnessScore::where('user_id', $userId)
            ->where('date', '>=', now()->subDays($days))
            ->orderBy('date', 'desc')
            ->get()
            ->map(fn($w) => $this->formatWellnessResponse($w));

        return response()->json([
            'success' => true,
            'data' => $history,
        ]);
    }

    /**
     * Get all today's wellness scores (admin view).
     */
    public function adminIndex(Request $request): JsonResponse
    {
        $date = $request->input('date', today()->toDateString());

        $query = WellnessScore::with('user')
            ->where('date', $date)
            ->orderBy('wellness_score', 'asc'); // Show lowest scores first

        // Filter by alert level
        if ($request->has('alert') && $request->alert !== 'all') {
            $query->where('overall_alert', $request->alert);
        }

        $scores = $query->get()->map(function ($wellness) {
            return [
                'id' => $wellness->id,
                'user_id' => $wellness->user_id,
                'user_name' => $wellness->user->name ?? 'Άγνωστος',
                'date' => $wellness->date->format('Y-m-d'),
                'wellness_score' => $wellness->wellness_score,
                'alert_emoji' => $wellness->getAlertEmoji(),
                'overall_alert' => $wellness->overall_alert,
                'tooltip' => $wellness->getTooltipMessage(),
                'metrics' => [
                    'sleep' => [
                        'value' => $wellness->sleep_hours,
                        'alert' => $wellness->sleep_alert,
                        'display' => $wellness->sleep_hours ? $wellness->sleep_hours . 'h' : '-',
                    ],
                    'hydration' => [
                        'value' => $wellness->hydration_liters,
                        'alert' => $wellness->hydration_alert,
                        'display' => $wellness->hydration_liters ? $wellness->hydration_liters . 'L' : '-',
                    ],
                    'calories' => [
                        'value' => $wellness->calories_consumed,
                        'percentage' => $wellness->calories_percentage,
                        'alert' => $wellness->calories_alert,
                        'display' => $wellness->calories_consumed ? $wellness->calories_consumed . ' kcal' : '-',
                    ],
                    'energy' => $wellness->energy_level,
                    'mood' => $wellness->mood_level,
                ],
            ];
        });

        // Summary stats
        $summary = [
            'total_submitted' => $scores->count(),
            'green_count' => $scores->where('overall_alert', 'green')->count(),
            'orange_count' => $scores->where('overall_alert', 'orange')->count(),
            'red_count' => $scores->where('overall_alert', 'red')->count(),
            'avg_score' => $scores->avg('wellness_score') ?? 0,
        ];

        return response()->json([
            'success' => true,
            'data' => $scores,
            'summary' => $summary,
            'date' => $date,
        ]);
    }

    /**
     * Get wellness thresholds (admin).
     */
    public function getThresholds(): JsonResponse
    {
        $thresholds = WellnessThreshold::getAllForAdmin();

        return response()->json([
            'success' => true,
            'data' => $thresholds,
        ]);
    }

    /**
     * Update wellness threshold (admin).
     */
    public function updateThreshold(Request $request, WellnessThreshold $threshold): JsonResponse
    {
        $validated = $request->validate([
            'min_value' => 'nullable|numeric',
            'max_value' => 'nullable|numeric',
            'label_el' => 'nullable|string|max:255',
            'tooltip_el' => 'nullable|string|max:500',
        ]);

        $threshold->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Το όριο ενημερώθηκε',
            'data' => $threshold,
        ]);
    }

    /**
     * Get users who haven't submitted wellness today.
     */
    public function getMissingSubmissions(Request $request): JsonResponse
    {
        $date = $request->input('date', today()->toDateString());

        // Get active users (you might want to filter by active membership)
        $submittedUserIds = WellnessScore::where('date', $date)
            ->pluck('user_id');

        $missingUsers = User::where('membership_type', '!=', 'Admin')
            ->where('membership_type', '!=', 'Staff')
            ->whereNotIn('id', $submittedUserIds)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'phone']);

        return response()->json([
            'success' => true,
            'data' => $missingUsers,
            'count' => $missingUsers->count(),
        ]);
    }

    /**
     * Get users with orange or red alerts (admin).
     */
    public function getUsersWithAlerts(Request $request): JsonResponse
    {
        $date = $request->input('date', today()->toDateString());
        $alertLevel = $request->input('level'); // 'orange', 'red', or null for both

        $query = WellnessScore::with('user')
            ->where('date', $date)
            ->whereIn('overall_alert', $alertLevel ? [$alertLevel] : ['orange', 'red']);

        $scores = $query->orderByRaw("
            CASE overall_alert
                WHEN 'red' THEN 1
                WHEN 'orange' THEN 2
                ELSE 3
            END
        ")->get()->map(function ($wellness) {
            return [
                'id' => $wellness->id,
                'user_id' => $wellness->user_id,
                'user_name' => $wellness->user->name ?? 'Άγνωστος',
                'user_email' => $wellness->user->email ?? '',
                'user_phone' => $wellness->user->phone ?? '',
                'overall_alert' => $wellness->overall_alert,
                'alert_emoji' => $wellness->getAlertEmoji(),
                'tooltip' => $wellness->getTooltipMessage(),
                'wellness_score' => $wellness->wellness_score,
                'alerts' => [
                    'sleep' => $wellness->sleep_alert,
                    'hydration' => $wellness->hydration_alert,
                    'calories' => $wellness->calories_alert,
                ],
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $scores,
            'count' => $scores->count(),
            'date' => $date,
        ]);
    }

    /**
     * Get single user's wellness history and current status (admin).
     */
    public function getUserWellness(Request $request, int $userId): JsonResponse
    {
        $user = User::find($userId);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        $days = $request->input('days', 30);

        $history = WellnessScore::where('user_id', $userId)
            ->where('date', '>=', now()->subDays($days))
            ->orderBy('date', 'desc')
            ->get();

        // Calculate averages
        $avgScore = $history->avg('wellness_score');
        $avgSleep = $history->avg('sleep_hours');
        $avgHydration = $history->avg('hydration_liters');

        // Count alerts
        $redCount = $history->where('overall_alert', 'red')->count();
        $orangeCount = $history->where('overall_alert', 'orange')->count();
        $greenCount = $history->where('overall_alert', 'green')->count();

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'summary' => [
                'avg_score' => round($avgScore ?? 0, 1),
                'avg_sleep' => round($avgSleep ?? 0, 1),
                'avg_hydration' => round($avgHydration ?? 0, 1),
                'total_entries' => $history->count(),
                'red_alerts' => $redCount,
                'orange_alerts' => $orangeCount,
                'green_alerts' => $greenCount,
            ],
            'history' => $history->map(fn($w) => $this->formatWellnessResponse($w)),
        ]);
    }

    /**
     * Get wellness thresholds for admin editing.
     */
    public function getThresholdsAdmin(): JsonResponse
    {
        $thresholds = WellnessThreshold::getAllForAdmin();

        return response()->json([
            'success' => true,
            'data' => $thresholds,
        ]);
    }

    /**
     * Get wellness analytics summary (admin).
     */
    public function analytics(Request $request): JsonResponse
    {
        $startDate = $request->input('start_date', now()->subDays(30)->toDateString());
        $endDate = $request->input('end_date', today()->toDateString());

        $scores = WellnessScore::whereBetween('date', [$startDate, $endDate])->get();

        // Daily breakdown
        $dailyStats = $scores->groupBy('date')->map(function ($dayScores) {
            return [
                'total' => $dayScores->count(),
                'avg_score' => round($dayScores->avg('wellness_score') ?? 0, 1),
                'red_count' => $dayScores->where('overall_alert', 'red')->count(),
                'orange_count' => $dayScores->where('overall_alert', 'orange')->count(),
                'green_count' => $dayScores->where('overall_alert', 'green')->count(),
            ];
        });

        // Overall metrics
        $overall = [
            'total_submissions' => $scores->count(),
            'unique_users' => $scores->pluck('user_id')->unique()->count(),
            'avg_score' => round($scores->avg('wellness_score') ?? 0, 1),
            'avg_sleep' => round($scores->avg('sleep_hours') ?? 0, 1),
            'avg_hydration' => round($scores->avg('hydration_liters') ?? 0, 1),
            'alert_distribution' => [
                'green' => $scores->where('overall_alert', 'green')->count(),
                'orange' => $scores->where('overall_alert', 'orange')->count(),
                'red' => $scores->where('overall_alert', 'red')->count(),
            ],
        ];

        // Most common issues
        $issues = [];
        $lowSleep = $scores->whereIn('sleep_alert', ['orange', 'red'])->count();
        $lowHydration = $scores->whereIn('hydration_alert', ['orange', 'red'])->count();
        $lowCalories = $scores->whereIn('calories_alert', ['orange', 'red'])->count();

        if ($scores->count() > 0) {
            $issues = [
                'low_sleep' => round(($lowSleep / $scores->count()) * 100, 1),
                'low_hydration' => round(($lowHydration / $scores->count()) * 100, 1),
                'low_calories' => round(($lowCalories / $scores->count()) * 100, 1),
            ];
        }

        return response()->json([
            'success' => true,
            'period' => [
                'start' => $startDate,
                'end' => $endDate,
            ],
            'overall' => $overall,
            'common_issues_pct' => $issues,
            'daily_breakdown' => $dailyStats,
        ]);
    }

    /**
     * Format wellness response for API.
     */
    private function formatWellnessResponse(WellnessScore $wellness): array
    {
        return [
            'id' => $wellness->id,
            'date' => $wellness->date->format('Y-m-d'),
            'wellness_score' => $wellness->wellness_score,
            'alert_emoji' => $wellness->getAlertEmoji(),
            'overall_alert' => $wellness->overall_alert,
            'tooltip' => $wellness->getTooltipMessage(),
            'sleep' => [
                'hours' => $wellness->sleep_hours,
                'quality' => $wellness->sleep_quality,
                'alert' => $wellness->sleep_alert,
            ],
            'hydration' => [
                'liters' => $wellness->hydration_liters,
                'alert' => $wellness->hydration_alert,
            ],
            'calories' => [
                'consumed' => $wellness->calories_consumed,
                'tdee' => $wellness->tdee,
                'percentage' => $wellness->calories_percentage,
                'alert' => $wellness->calories_alert,
            ],
            'subjective' => [
                'energy' => $wellness->energy_level,
                'mood' => $wellness->mood_level,
                'stress' => $wellness->stress_level,
                'soreness' => $wellness->soreness_level,
            ],
            'hrv' => $wellness->hrv,
            'notes' => $wellness->notes,
            'created_at' => $wellness->created_at->toISOString(),
        ];
    }
}
