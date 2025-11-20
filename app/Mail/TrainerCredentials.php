<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TrainerCredentials extends Mailable
{
    use Queueable, SerializesModels;

    public $trainerName;
    public $email;
    public $password;

    /**
     * Create a new message instance.
     */
    public function __construct($trainerName, $email, $password)
    {
        $this->trainerName = $trainerName;
        $this->email = $email;
        $this->password = $password;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('Καλώς ήρθες στο Sweat 93 - Στοιχεία Σύνδεσης')
                    ->view('emails.trainer-credentials');
    }
}
