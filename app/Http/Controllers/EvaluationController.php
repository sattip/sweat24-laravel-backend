<?php

namespace App\Http\Controllers;

use App\Models\ClassEvaluation;
use App\Models\Booking;
use App\Models\GymClass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EvaluationController extends Controller
{
    /**
     * Create evaluation links after class completion
     */
    public function createEvaluationForCompletedClass(GymClass $class)
    {
        // Only create evaluations for classes that ended at least 30 minutes ago
        if ($class->date->setTimeFromTimeString($class->time)->addMinutes($class->duration + 30)->isFuture()) {
            return response()->json([
                'success' => false,
                'message' => 'Το μάθημα δεν έχει ολοκληρωθεί ακόμα'
            ], 400);
        }

        $bookings = $class->bookings()
            ->where('status', 'completed')
            ->where('attended', true)
            ->get();

        $created = 0;
        foreach ($bookings as $booking) {
            // Check if evaluation already exists
            $existing = ClassEvaluation::where('booking_id', $booking->id)->first();
            if (!$existing) {
                ClassEvaluation::create([
                    'class_id' => $class->id,
                    'booking_id' => $booking->id,
                    'sent_at' => now(),
                ]);
                $created++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Δημιουργήθηκαν $created αξιολογήσεις",
            'total_attendees' => $bookings->count(),
            'evaluations_created' => $created
        ]);
    }

    /**
     * Get evaluation by token (anonymous access)
     */
    public function getByToken($token)
    {
        $evaluation = ClassEvaluation::where('evaluation_token', $token)
            ->with(['gymClass.instructor'])
            ->first();

        if (!$evaluation) {
            return response()->json([
                'success' => false,
                'message' => 'Η αξιολόγηση δεν βρέθηκε'
            ], 404);
        }

        if ($evaluation->isExpired()) {
            return response()->json([
                'success' => false,
                'message' => 'Η αξιολόγηση έχει λήξει'
            ], 410);
        }

        if ($evaluation->is_submitted) {
            return response()->json([
                'success' => false,
                'message' => 'Η αξιολόγηση έχει ήδη υποβληθεί'
            ], 400);
        }

        return response()->json([
            'success' => true,
            'evaluation' => [
                'token' => $evaluation->evaluation_token,
                'class' => [
                    'name' => $evaluation->gymClass->name,
                    'date' => $evaluation->gymClass->date->format('d/m/Y'),
                    'time' => $evaluation->gymClass->time,
                    'instructor' => $evaluation->gymClass->instructor->name ?? 'N/A',
                    'type' => $evaluation->gymClass->type,
                ],
                'expires_at' => $evaluation->expires_at->format('d/m/Y H:i'),
                'available_tags' => ClassEvaluation::getAvailableTags(),
            ]
        ]);
    }

    /**
     * Submit evaluation (anonymous)
     */
    public function submit(Request $request, $token)
    {
        $evaluation = ClassEvaluation::where('evaluation_token', $token)->first();

        if (!$evaluation || !$evaluation->canBeSubmitted()) {
            return response()->json([
                'success' => false,
                'message' => 'Δεν μπορείτε να υποβάλετε αυτή την αξιολόγηση'
            ], 400);
        }

        $validated = $request->validate([
            'overall_rating' => 'required|integer|min:1|max:5',
            'instructor_rating' => 'required|integer|min:1|max:5',
            'facility_rating' => 'required|integer|min:1|max:5',
            'comments' => 'nullable|string|max:1000',
            'tags' => 'nullable|array',
            'tags.*' => 'string|in:' . implode(',', array_keys(ClassEvaluation::getAvailableTags())),
            'would_recommend' => 'required|boolean',
        ]);

        $evaluation->update([
            ...$validated,
            'is_submitted' => true,
            'submitted_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Ευχαριστούμε για την αξιολόγησή σας!'
        ]);
    }

    /**
     * Get class evaluation statistics (admin/trainer)
     */
    public function classStats(GymClass $class)
    {
        $evaluations = $class->evaluations()->submitted()->get();
        
        if ($evaluations->isEmpty()) {
            return response()->json([
                'success' => true,
                'stats' => null,
                'message' => 'Δεν υπάρχουν αξιολογήσεις για αυτό το μάθημα'
            ]);
        }

        $stats = [
            'total_evaluations' => $evaluations->count(),
            'average_overall' => round($evaluations->avg('overall_rating'), 1),
            'average_instructor' => round($evaluations->avg('instructor_rating'), 1),
            'average_facility' => round($evaluations->avg('facility_rating'), 1),
            'would_recommend_percentage' => round($evaluations->where('would_recommend', true)->count() / $evaluations->count() * 100),
            'rating_distribution' => [],
            'top_tags' => [],
            'recent_comments' => []
        ];

        // Rating distribution
        for ($i = 1; $i <= 5; $i++) {
            $stats['rating_distribution'][$i] = $evaluations->where('overall_rating', $i)->count();
        }

        // Top tags
        $allTags = $evaluations->pluck('tags')->flatten()->filter();
        $tagCounts = $allTags->countBy();
        $stats['top_tags'] = $tagCounts->sortDesc()->take(5)->map(function ($count, $tag) {
            return [
                'tag' => $tag,
                'label' => ClassEvaluation::getAvailableTags()[$tag] ?? $tag,
                'count' => $count
            ];
        })->values();

        // Recent comments (anonymous)
        $stats['recent_comments'] = $evaluations
            ->whereNotNull('comments')
            ->sortByDesc('submitted_at')
            ->take(5)
            ->map(function ($eval) {
                return [
                    'rating' => $eval->overall_rating,
                    'comment' => $eval->comments,
                    'date' => $eval->submitted_at->format('d/m/Y'),
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'class' => [
                'id' => $class->id,
                'name' => $class->name,
                'date' => $class->date->format('d/m/Y'),
                'instructor' => $class->instructor->name ?? 'N/A',
            ],
            'stats' => $stats
        ]);
    }

    /**
     * Get instructor evaluation statistics
     */
    public function instructorStats($instructorId)
    {
        $evaluations = ClassEvaluation::submitted()
            ->whereHas('gymClass', function ($query) use ($instructorId) {
                $query->where('instructor_id', $instructorId);
            })
            ->get();

        if ($evaluations->isEmpty()) {
            return response()->json([
                'success' => true,
                'stats' => null,
                'message' => 'Δεν υπάρχουν αξιολογήσεις για αυτόν τον εκπαιδευτή'
            ]);
        }

        $stats = [
            'total_evaluations' => $evaluations->count(),
            'average_instructor_rating' => round($evaluations->avg('instructor_rating'), 1),
            'average_overall_rating' => round($evaluations->avg('overall_rating'), 1),
            'would_recommend_percentage' => round($evaluations->where('would_recommend', true)->count() / $evaluations->count() * 100),
            'evaluations_by_month' => [],
            'top_positive_tags' => [],
        ];

        // Evaluations by month (last 6 months)
        $monthlyEvals = $evaluations->groupBy(function ($eval) {
            return $eval->submitted_at->format('Y-m');
        })->sortKeys()->take(-6);

        foreach ($monthlyEvals as $month => $evals) {
            $stats['evaluations_by_month'][$month] = [
                'count' => $evals->count(),
                'average_rating' => round($evals->avg('instructor_rating'), 1),
            ];
        }

        // Top positive tags
        $positiveTags = ['excellent_instructor', 'motivating', 'well_organized', 'great_atmosphere'];
        $allTags = $evaluations->pluck('tags')->flatten()->filter();
        $stats['top_positive_tags'] = $allTags
            ->filter(fn($tag) => in_array($tag, $positiveTags))
            ->countBy()
            ->sortDesc()
            ->take(3)
            ->map(function ($count, $tag) {
                return [
                    'tag' => $tag,
                    'label' => ClassEvaluation::getAvailableTags()[$tag] ?? $tag,
                    'count' => $count
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'stats' => $stats
        ]);
    }

    /**
     * Get pending evaluations count for admin dashboard
     */
    public function pendingCount()
    {
        $pending = ClassEvaluation::pending()->count();
        $expired = ClassEvaluation::expired()->count();
        $submitted_today = ClassEvaluation::submitted()
            ->whereDate('submitted_at', today())
            ->count();

        return response()->json([
            'success' => true,
            'counts' => [
                'pending' => $pending,
                'expired' => $expired,
                'submitted_today' => $submitted_today,
            ]
        ]);
    }
}