<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentProcessed
{
    use Dispatchable, SerializesModels;

    public $user;
    public $amount;
    public $description;
    public $reference;
    public $paymentMethod;

    /**
     * Create a new event instance.
     */
    public function __construct(User $user, $amount, $description, $reference = null, $paymentMethod = 'cash')
    {
        $this->user = $user;
        $this->amount = $amount;
        $this->description = $description;
        $this->reference = $reference;
        $this->paymentMethod = $paymentMethod;
    }
}
