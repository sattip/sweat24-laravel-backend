<?php

namespace App\Observers;

use App\Models\Order;
use App\Services\PointsService;
use Illuminate\Support\Facades\Log;

class OrderObserver
{
    protected $pointsService;

    public function __construct(PointsService $pointsService)
    {
        $this->pointsService = $pointsService;
    }

    /**
     * Handle the Order "created" event.
     */
    public function created(Order $order): void
    {
        //
    }

    /**
     * Handle the Order "updated" event.
     */
    public function updated(Order $order): void
    {
        // Check if order status changed to completed
        if ($order->wasChanged('status') && $order->status === 'completed') {
            $this->handleOrderCompleted($order);
        }
    }

    /**
     * Handle order completion and award points
     */
    protected function handleOrderCompleted(Order $order): void
    {
        try {
            // Award points for the completed order
            $success = $this->pointsService->awardPointsForOrder($order);
            
            if ($success) {
                Log::info("Points awarded successfully for completed order", [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'user_id' => $order->user_id,
                    'points_awarded' => $order->points_awarded
                ]);
            } else {
                Log::warning("Failed to award points for completed order", [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'user_id' => $order->user_id
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Exception while awarding points for completed order", [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Handle the Order "deleted" event.
     */
    public function deleted(Order $order): void
    {
        //
    }

    /**
     * Handle the Order "restored" event.
     */
    public function restored(Order $order): void
    {
        //
    }

    /**
     * Handle the Order "force deleted" event.
     */
    public function forceDeleted(Order $order): void
    {
        //
    }
}
