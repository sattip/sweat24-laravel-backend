<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\ChurnFeedbackSurvey;
use App\Models\ChurnFeedback;
use App\Models\UserPackage;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class ChurnFeedbackController extends Controller
{
    /**
     * Get pending survey for user (mobile app).
     */
    public function getPendingSurvey(Request $request): JsonResponse
    {
        $userId = $request->input('user_id') ?? $request->user()?->id;

        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'User ID required'
            ], 401);
        }

        $survey = ChurnFeedback::where('user_id', $userId)
            ->where('status', ChurnFeedback::STATUS_PENDING)
            ->whereNotNull('sent_at')
            ->whereNull('responded_at')
            ->where('opted_out', false)
            ->with(['userPackage.package'])
            ->first();

        if (!$survey) {
            return response()->json([
                'success' => true,
                'data' => null,
                'message' => 'Δεν υπάρχει ερωτηματολόγιο προς απάντηση'
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $survey->id,
                'expired_at' => $survey->expired_at,
                'package_name' => $survey->userPackage?->name,
                'is_reminder' => $survey->reminder_sent_at !== null,
                'reason_options' => ChurnFeedback::getReasonLabels(),
            ]
        ]);
    }

    /**
     * Submit quick response (single reason selection).
     */
    public function submitQuickResponse(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'feedback_id' => 'required|exists:churn_feedback,id',
            'reason' => 'required|string',
            'comment' => 'nullable|string|max:1000',
        ]);

        $feedback = ChurnFeedback::findOrFail($validated['feedback_id']);

        // Check if already responded
        if ($feedback->hasResponded()) {
            return response()->json([
                'success' => false,
                'message' => 'Έχετε ήδη απαντήσει σε αυτό το ερωτηματολόγιο'
            ], 400);
        }

        $reasons = [$validated['reason']];
        $feedback->reasons = $reasons;
        $feedback->setReasonFlags($reasons);
        $feedback->comment = $validated['comment'] ?? null;
        $feedback->survey_type = 'quick';
        $feedback->responded_at = now();

        // If user selected "will continue", don't show mini survey
        if ($feedback->reason_will_continue) {
            $feedback->save();

            return response()->json([
                'success' => true,
                'message' => 'Χαιρόμαστε που θα συνεχίσεις! Θα σε ενημερώσουμε σύντομα.',
                'status' => 'pause',
                'show_mini_survey' => false
            ]);
        }

        $feedback->save();

        return response()->json([
            'success' => true,
            'message' => 'Ευχαριστούμε για την απάντηση!',
            'status' => $feedback->status,
            'show_mini_survey' => true,
            'feedback_id' => $feedback->id
        ]);
    }

    /**
     * Submit full mini survey.
     */
    public function submitMiniSurvey(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'feedback_id' => 'required|exists:churn_feedback,id',
            'reasons' => 'nullable|array',
            'reasons.*' => 'string',
            'improvements' => 'nullable|array',
            'improvements.*' => 'string',
            'improvement_comment' => 'nullable|string|max:1000',
            'return_intent_score' => 'nullable|integer|min:0|max:10',
            'wants_alternative_package' => 'nullable|boolean',
            'future_return_intent' => 'nullable|in:yes,maybe,no',
            'no_return_comment' => 'nullable|string|max:1000',
            'comment' => 'nullable|string|max:1000',
        ]);

        $feedback = ChurnFeedback::findOrFail($validated['feedback_id']);

        // Update with mini survey data
        if (!empty($validated['reasons'])) {
            $feedback->reasons = $validated['reasons'];
            $feedback->setReasonFlags($validated['reasons']);
        }

        $feedback->survey_type = 'mini';
        $feedback->improvements = $validated['improvements'] ?? null;
        $feedback->improvement_comment = $validated['improvement_comment'] ?? null;
        $feedback->return_intent_score = $validated['return_intent_score'] ?? null;
        $feedback->wants_alternative_package = $validated['wants_alternative_package'] ?? false;
        $feedback->future_return_intent = $validated['future_return_intent'] ?? null;
        $feedback->no_return_comment = $validated['no_return_comment'] ?? null;

        if (!empty($validated['comment'])) {
            $feedback->comment = $validated['comment'];
        }

        $feedback->responded_at = now();

        // Determine win-back offer if user wants alternative package
        if ($feedback->wants_alternative_package) {
            $feedback->winback_offer_type = $feedback->determineWinbackOffer();
            $feedback->winback_consent = true;
        }

        $feedback->save();

        // Prepare response based on winback eligibility
        $response = [
            'success' => true,
            'message' => 'Ευχαριστούμε πολύ για το feedback σου!',
            'status' => $feedback->status,
        ];

        if ($feedback->winback_offer_type && $feedback->wants_alternative_package) {
            $response['winback_offer'] = [
                'type' => $feedback->winback_offer_type,
                'label' => ChurnFeedback::getWinbackOfferLabels()[$feedback->winback_offer_type] ?? null,
            ];
            $response['message'] = 'Ευχαριστούμε! Θα επικοινωνήσουμε σύντομα με μια προσφορά για εσένα.';
        }

        return response()->json($response);
    }

    /**
     * Opt-out from churn surveys.
     */
    public function optOut(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'feedback_id' => 'required|exists:churn_feedback,id',
        ]);

        $feedback = ChurnFeedback::findOrFail($validated['feedback_id']);
        $feedback->opted_out = true;
        $feedback->opted_out_at = now();
        $feedback->save();

        return response()->json([
            'success' => true,
            'message' => 'Δεν θα λαμβάνεις πλέον τέτοια μηνύματα.'
        ]);
    }

    /**
     * Get all churn feedback (admin).
     */
    public function index(Request $request): JsonResponse
    {
        $query = ChurnFeedback::with(['user', 'userPackage.package'])
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->has('from_date')) {
            $query->where('expired_at', '>=', $request->from_date);
        }
        if ($request->has('to_date')) {
            $query->where('expired_at', '<=', $request->to_date);
        }

        // Filter by responded
        if ($request->has('responded') && $request->responded === 'true') {
            $query->whereNotNull('responded_at');
        }

        $feedback = $query->paginate($request->input('per_page', 50));

        return response()->json([
            'success' => true,
            'data' => $feedback
        ]);
    }

    /**
     * Get single churn feedback details (admin).
     */
    public function show(ChurnFeedback $churnFeedback): JsonResponse
    {
        $churnFeedback->load(['user', 'userPackage.package']);

        return response()->json([
            'success' => true,
            'data' => $churnFeedback
        ]);
    }

    /**
     * Get analytics summary.
     */
    public function analytics(Request $request): JsonResponse
    {
        $fromDate = $request->input('from_date', now()->subMonths(3)->toDateString());
        $toDate = $request->input('to_date', now()->toDateString());

        // Base query for date range
        $baseQuery = ChurnFeedback::whereBetween('expired_at', [$fromDate, $toDate]);

        // Total counts
        $totalExpired = (clone $baseQuery)->count();
        $totalResponded = (clone $baseQuery)->whereNotNull('responded_at')->count();
        $totalChurned = (clone $baseQuery)->where('status', ChurnFeedback::STATUS_CHURN)->count();
        $totalPaused = (clone $baseQuery)->where('status', ChurnFeedback::STATUS_PAUSE)->count();
        $totalRenewed = (clone $baseQuery)->where('status', ChurnFeedback::STATUS_RENEWED)->count();

        // Calculate rates
        $churnRate = $totalExpired > 0 ? round(($totalChurned / $totalExpired) * 100, 1) : 0;
        $pauseRate = $totalExpired > 0 ? round(($totalPaused / $totalExpired) * 100, 1) : 0;
        $renewRate = $totalExpired > 0 ? round(($totalRenewed / $totalExpired) * 100, 1) : 0;
        $responseRate = $totalExpired > 0 ? round(($totalResponded / $totalExpired) * 100, 1) : 0;

        // Return after pause rate
        $pausedThatReturned = ChurnFeedback::where('status', ChurnFeedback::STATUS_PAUSE)
            ->whereBetween('expired_at', [$fromDate, $toDate])
            ->whereHas('user', function ($q) {
                $q->whereHas('userPackages', function ($pq) {
                    $pq->where('status', 'active');
                });
            })
            ->count();
        $returnAfterPauseRate = $totalPaused > 0 ? round(($pausedThatReturned / $totalPaused) * 100, 1) : 0;

        // Reason breakdown
        $reasons = [
            'will_continue' => (clone $baseQuery)->where('reason_will_continue', true)->count(),
            'price_value' => (clone $baseQuery)->where('reason_price_value', true)->count(),
            'financial_issue' => (clone $baseQuery)->where('reason_financial_issue', true)->count(),
            'schedule' => (clone $baseQuery)->where('reason_schedule', true)->count(),
            'program_mismatch' => (clone $baseQuery)->where('reason_program_mismatch', true)->count(),
            'trainer_mismatch' => (clone $baseQuery)->where('reason_trainer_mismatch', true)->count(),
            'distance' => (clone $baseQuery)->where('reason_distance', true)->count(),
            'health' => (clone $baseQuery)->where('reason_health', true)->count(),
            'priorities' => (clone $baseQuery)->where('reason_priorities', true)->count(),
            'other' => (clone $baseQuery)->where('reason_other', true)->count(),
        ];

        // Sort reasons by count and get top 5
        arsort($reasons);
        $topReasons = array_slice($reasons, 0, 5, true);
        $topReasonsWithLabels = [];
        $reasonLabels = ChurnFeedback::getReasonLabels();
        foreach ($topReasons as $key => $count) {
            $topReasonsWithLabels[] = [
                'reason' => $key,
                'label' => $reasonLabels[$key] ?? $key,
                'count' => $count,
                'percentage' => $totalResponded > 0 ? round(($count / $totalResponded) * 100, 1) : 0,
            ];
        }

        // Future return intent breakdown
        $futureReturnIntent = [
            'yes' => (clone $baseQuery)->where('future_return_intent', 'yes')->count(),
            'maybe' => (clone $baseQuery)->where('future_return_intent', 'maybe')->count(),
            'no' => (clone $baseQuery)->where('future_return_intent', 'no')->count(),
        ];

        // Average return intent score
        $avgReturnIntent = (clone $baseQuery)
            ->whereNotNull('return_intent_score')
            ->avg('return_intent_score');

        // Win-back performance
        $winbackOffered = (clone $baseQuery)->whereNotNull('winback_offer_type')->count();
        $winbackAccepted = (clone $baseQuery)->where('winback_accepted', true)->count();
        $winbackAcceptanceRate = $winbackOffered > 0 ? round(($winbackAccepted / $winbackOffered) * 100, 1) : 0;

        // Win-back by type
        $winbackByType = (clone $baseQuery)
            ->whereNotNull('winback_offer_type')
            ->select('winback_offer_type', DB::raw('COUNT(*) as offered'), DB::raw('SUM(winback_accepted) as accepted'))
            ->groupBy('winback_offer_type')
            ->get()
            ->map(function ($item) {
                $item->acceptance_rate = $item->offered > 0 ? round(($item->accepted / $item->offered) * 100, 1) : 0;
                $item->label = ChurnFeedback::getWinbackOfferLabels()[$item->winback_offer_type] ?? $item->winback_offer_type;
                return $item;
            });

        // Monthly trends (last 6 months)
        $monthlyTrends = ChurnFeedback::select(
            DB::raw("DATE_FORMAT(expired_at, '%Y-%m') as month"),
            DB::raw('COUNT(*) as total'),
            DB::raw("SUM(CASE WHEN status = 'churn' THEN 1 ELSE 0 END) as churned"),
            DB::raw("SUM(CASE WHEN status = 'pause' THEN 1 ELSE 0 END) as paused"),
            DB::raw("SUM(CASE WHEN status = 'renewed' THEN 1 ELSE 0 END) as renewed"),
            DB::raw('AVG(return_intent_score) as avg_return_intent')
        )
            ->where('expired_at', '>=', now()->subMonths(6))
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Reason vs Return Intent correlation
        $reasonReturnCorrelation = [];
        foreach (array_keys($reasons) as $reason) {
            $reasonField = "reason_{$reason}";
            $avgIntent = ChurnFeedback::where($reasonField, true)
                ->whereNotNull('future_return_intent')
                ->whereBetween('expired_at', [$fromDate, $toDate])
                ->get();

            if ($avgIntent->count() > 0) {
                $yesCount = $avgIntent->where('future_return_intent', 'yes')->count();
                $maybeCount = $avgIntent->where('future_return_intent', 'maybe')->count();
                $noCount = $avgIntent->where('future_return_intent', 'no')->count();
                $total = $avgIntent->count();

                $reasonReturnCorrelation[$reason] = [
                    'label' => $reasonLabels[$reason] ?? $reason,
                    'total' => $total,
                    'yes_pct' => round(($yesCount / $total) * 100, 1),
                    'maybe_pct' => round(($maybeCount / $total) * 100, 1),
                    'no_pct' => round(($noCount / $total) * 100, 1),
                ];
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => [
                    'total_expired' => $totalExpired,
                    'total_responded' => $totalResponded,
                    'response_rate' => $responseRate,
                    'churn_rate' => $churnRate,
                    'pause_rate' => $pauseRate,
                    'renew_rate' => $renewRate,
                    'return_after_pause_rate' => $returnAfterPauseRate,
                ],
                'reasons' => [
                    'all' => $reasons,
                    'top_5' => $topReasonsWithLabels,
                    'labels' => $reasonLabels,
                ],
                'return_intent' => [
                    'average_score' => round($avgReturnIntent ?? 0, 1),
                    'future_intent' => $futureReturnIntent,
                ],
                'winback' => [
                    'offered' => $winbackOffered,
                    'accepted' => $winbackAccepted,
                    'acceptance_rate' => $winbackAcceptanceRate,
                    'by_type' => $winbackByType,
                ],
                'trends' => $monthlyTrends,
                'reason_return_correlation' => $reasonReturnCorrelation,
            ]
        ]);
    }

    /**
     * Mark feedback as renewed (called when user renews package).
     */
    public function markAsRenewed(int $userId): void
    {
        ChurnFeedback::where('user_id', $userId)
            ->whereIn('status', [ChurnFeedback::STATUS_PENDING, ChurnFeedback::STATUS_PAUSE])
            ->update(['status' => ChurnFeedback::STATUS_RENEWED]);
    }

    /**
     * Trigger churn feedback for a user manually (admin action).
     * This is used when admin clicks "Επιθυμεί Διακοπή" button.
     */
    public function triggerForUser(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'notes' => 'nullable|string|max:1000',
        ]);

        $userId = $validated['user_id'];
        $user = User::find($userId);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Ο χρήστης δεν βρέθηκε'
            ], 404);
        }

        // Check if there's already a pending feedback for this user
        $existingPending = ChurnFeedback::where('user_id', $userId)
            ->where('status', ChurnFeedback::STATUS_PENDING)
            ->whereNull('responded_at')
            ->first();

        if ($existingPending) {
            return response()->json([
                'success' => false,
                'message' => 'Υπάρχει ήδη ενεργό ερωτηματολόγιο αποχώρησης για αυτόν τον χρήστη',
                'data' => $existingPending
            ], 400);
        }

        // Get the user's active or most recent package
        $userPackage = UserPackage::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->first();

        // Create the churn feedback record
        $feedback = ChurnFeedback::create([
            'user_id' => $userId,
            'user_package_id' => $userPackage?->id,
            'expired_at' => now(),
            'sent_at' => now(),
            'status' => ChurnFeedback::STATUS_PENDING,
            'comment' => $validated['notes'] ?? null,
            'trigger_source' => 'admin_manual', // Mark that this was triggered manually
        ]);

        // Send email notification to user
        $emailSent = false;
        if ($user->email) {
            try {
                Mail::to($user->email)->send(new ChurnFeedbackSurvey($user, $feedback));
                $emailSent = true;
            } catch (\Exception $e) {
                // Log the error but don't fail the request
                \Log::error('Failed to send churn feedback email: ' . $e->getMessage(), [
                    'user_id' => $userId,
                    'feedback_id' => $feedback->id,
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => $emailSent
                ? 'Το ερωτηματολόγιο αποχώρησης στάλθηκε επιτυχώς στον χρήστη (και με email)'
                : 'Το ερωτηματολόγιο αποχώρησης δημιουργήθηκε (χωρίς email - δεν υπάρχει email χρήστη ή απέτυχε η αποστολή)',
            'data' => $feedback,
            'email_sent' => $emailSent
        ]);
    }
}
