<?php

namespace App\Http\Controllers;

use App\Models\ChurnFeedback;
use Illuminate\Http\Request;

class ChurnSurveyController extends Controller
{
    /**
     * Show the churn survey form.
     */
    public function show(string $token)
    {
        // Find feedback by ID (token is the feedback ID for simplicity)
        $feedback = ChurnFeedback::with(['user', 'userPackage.package'])
            ->find($token);

        if (!$feedback) {
            return view('churn-survey.not-found');
        }

        // Check if already responded
        if ($feedback->hasResponded()) {
            return view('churn-survey.already-responded', [
                'feedback' => $feedback
            ]);
        }

        // Check if opted out
        if ($feedback->opted_out) {
            return view('churn-survey.opted-out');
        }

        return view('churn-survey.form', [
            'feedback' => $feedback,
            'user' => $feedback->user,
            'reasonOptions' => ChurnFeedback::getReasonLabels(),
            'improvementOptions' => [
                'better_schedule' => 'Πιο ευέλικτο πρόγραμμα',
                'more_programs' => 'Περισσότερα προγράμματα',
                'better_prices' => 'Καλύτερες τιμές',
                'more_trainers' => 'Περισσότεροι προπονητές',
                'better_facilities' => 'Καλύτεροι χώροι/εξοπλισμός',
                'online_options' => 'Online επιλογές',
                'nutrition_support' => 'Διατροφική υποστήριξη',
                'personal_training' => 'Περισσότερο personal training',
            ],
        ]);
    }

    /**
     * Submit the churn survey.
     */
    public function submit(Request $request, string $token)
    {
        $feedback = ChurnFeedback::find($token);

        if (!$feedback) {
            return redirect()->back()->with('error', 'Το ερωτηματολόγιο δεν βρέθηκε.');
        }

        if ($feedback->hasResponded()) {
            return redirect()->route('churn-survey.thank-you');
        }

        $validated = $request->validate([
            'reason' => 'required|string',
            'other_reasons' => 'nullable|array',
            'comment' => 'nullable|string|max:1000',
            'improvements' => 'nullable|array',
            'improvement_comment' => 'nullable|string|max:1000',
            'return_intent_score' => 'nullable|integer|min:0|max:10',
            'future_return_intent' => 'nullable|in:yes,maybe,no',
            'wants_alternative_package' => 'nullable|boolean',
        ]);

        // Collect all reasons
        $reasons = [$validated['reason']];
        if (!empty($validated['other_reasons'])) {
            $reasons = array_merge($reasons, $validated['other_reasons']);
        }

        $feedback->reasons = $reasons;
        $feedback->setReasonFlags($reasons);
        $feedback->comment = $validated['comment'] ?? null;
        $feedback->survey_type = 'web';
        $feedback->improvements = $validated['improvements'] ?? null;
        $feedback->improvement_comment = $validated['improvement_comment'] ?? null;
        $feedback->return_intent_score = $validated['return_intent_score'] ?? null;
        $feedback->future_return_intent = $validated['future_return_intent'] ?? null;
        $feedback->wants_alternative_package = $request->boolean('wants_alternative_package');
        $feedback->responded_at = now();

        // Determine win-back offer if user wants alternative package
        if ($feedback->wants_alternative_package) {
            $feedback->winback_offer_type = $feedback->determineWinbackOffer();
            $feedback->winback_consent = true;
        }

        $feedback->save();

        return redirect()->route('churn-survey.thank-you');
    }

    /**
     * Show thank you page.
     */
    public function thankYou()
    {
        return view('churn-survey.thank-you');
    }

    /**
     * Opt-out from surveys.
     */
    public function optOut(string $token)
    {
        $feedback = ChurnFeedback::find($token);

        if ($feedback) {
            $feedback->opted_out = true;
            $feedback->opted_out_at = now();
            $feedback->save();
        }

        return view('churn-survey.opted-out-confirmed');
    }
}
