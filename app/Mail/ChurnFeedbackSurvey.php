<?php

namespace App\Mail;

use App\Models\ChurnFeedback;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ChurnFeedbackSurvey extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public ChurnFeedback $feedback;
    public string $surveyUrl;
    public string $webSurveyUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, ChurnFeedback $feedback)
    {
        $this->user = $user;
        $this->feedback = $feedback;
        // Deep link to open the survey in the mobile app
        $this->surveyUrl = 'sweat93://churn-survey/' . $feedback->id;
        // Web URL for browser access
        $this->webSurveyUrl = config('app.url') . '/survey/churn/' . $feedback->id;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('Sweat93 - Θα θέλαμε τη γνώμη σου')
                    ->view('emails.churn-feedback-survey');
    }
}
