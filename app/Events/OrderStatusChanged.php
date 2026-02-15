<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $order;
    public $previousStatus;
    public $newStatus;

    public function __construct(Order $order, string $previousStatus, string $newStatus)
    {
        $this->order = $order;
        $this->previousStatus = $previousStatus;
        $this->newStatus = $newStatus;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('order.' . $this->order->user_id),
        ];
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'previous_status' => $this->previousStatus,
            'new_status' => $this->newStatus,
            'total' => $this->order->total,
            'message' => $this->getStatusMessage(),
        ];
    }

    /**
     * Get status-specific message
     */
    private function getStatusMessage(): string
    {
        switch ($this->newStatus) {
            case 'processing':
                return "Η παραγγελία σας #{$this->order->order_number} βρίσκεται υπό επεξεργασία";
            case 'ready_for_pickup':
                return "Η παραγγελία σας #{$this->order->order_number} είναι έτοιμη για παραλαβή!";
            case 'completed':
                return "Η παραγγελία σας #{$this->order->order_number} ολοκληρώθηκε επιτυχώς";
            case 'cancelled':
                return "Η παραγγελία σας #{$this->order->order_number} ακυρώθηκε";
            default:
                return "Η κατάσταση της παραγγελίας σας #{$this->order->order_number} ενημερώθηκε";
        }
    }

    /**
     * Check if this is a ready for pickup status
     */
    public function isReadyForPickup(): bool
    {
        return $this->newStatus === 'ready_for_pickup';
    }

    /**
     * Check if this is a completion
     */
    public function isCompleted(): bool
    {
        return $this->newStatus === 'completed';
    }

    /**
     * Check if this is a cancellation
     */
    public function isCancelled(): bool
    {
        return $this->newStatus === 'cancelled';
    }

    /**
     * Check if this is a processing status
     */
    public function isProcessing(): bool
    {
        return $this->newStatus === 'processing';
    }
} 