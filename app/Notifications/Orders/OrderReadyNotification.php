<?php

namespace App\Notifications\Orders;

use App\Models\Order;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderReadyNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $order;
    protected $pickupInstructions;

    /**
     * Create a new notification instance.
     */
    public function __construct(Order $order, string $pickupInstructions = null)
    {
        $this->order = $order;
        $this->pickupInstructions = $pickupInstructions;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail']; // TEMP: disabled database
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your Order is Ready for Pickup - Sweat93')
            ->view('emails.orders.order-ready', [
                'order' => $this->order,
                'user' => $notifiable,
                'orderItems' => $this->order->items,
                'pickupInstructions' => $this->pickupInstructions,
            ]);
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        $orderNumber = $this->order->order_number ?? 'ORD-' . str_pad($this->order->id, 6, '0', STR_PAD_LEFT);
        
        return [
            'title' => 'Η παραγγελία σας είναι έτοιμη!',
            'message' => "Η παραγγελία σας #{$orderNumber} είναι έτοιμη για παραλαβή. Μπορείτε να την παραλάβετε από το γυμναστήριο.",
            'order_id' => $this->order->id,
            'order_number' => $orderNumber,
            'order_status' => $this->order->status,
            'total_amount' => $this->order->total,
            'items_count' => $this->order->items->count(),
            'pickup_instructions' => $this->pickupInstructions,
            'ready_date' => now()->format('Y-m-d'),
            'action_url' => '/orders/' . $this->order->id,
            'icon' => 'package-check',
            'type' => 'order_ready'
        ];
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}