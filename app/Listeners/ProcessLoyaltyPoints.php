<?php

namespace App\Listeners;

use App\Events\PaymentProcessed;
use App\Services\LoyaltyService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class ProcessLoyaltyPoints implements ShouldQueue
{
    use InteractsWithQueue;

    protected $loyaltyService;

    /**
     * Create the event listener.
     */
    public function __construct(LoyaltyService $loyaltyService)
    {
        $this->loyaltyService = $loyaltyService;
    }

    /**
     * Handle the event.
     */
    public function handle(PaymentProcessed $event): void
    {
        try {
            // Απόδοση πόντων για την πληρωμή (1 ευρώ = 1 πόντος)
            $loyaltyPoint = $this->loyaltyService->addPointsFromPayment(
                $event->user,
                $event->amount,
                $event->reference
            );

            if ($loyaltyPoint) {
                Log::info('Loyalty points awarded for payment', [
                    'user_id' => $event->user->id,
                    'payment_amount' => $event->amount,
                    'points_awarded' => $loyaltyPoint->amount,
                    'new_balance' => $event->user->fresh()->loyalty_points_balance,
                    'payment_description' => $event->description,
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Failed to process loyalty points for payment', [
                'user_id' => $event->user->id,
                'payment_amount' => $event->amount,
                'error' => $e->getMessage(),
            ]);

            // Re-throw to mark job as failed
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(PaymentProcessed $event, $exception): void
    {
        Log::error('Loyalty points processing job failed', [
            'user_id' => $event->user->id,
            'payment_amount' => $event->amount,
            'exception' => $exception->getMessage(),
        ]);
    }
}
