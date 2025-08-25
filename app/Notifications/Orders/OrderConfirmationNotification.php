<?php

namespace App\Notifications\Orders;

use App\Models\Order;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderConfirmationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $order;

    /**
     * Create a new notification instance.
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
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
            ->subject('Order Confirmation - Sweat93')
            ->view('emails.orders.order-confirmation', [
                'order' => $this->order,
                'user' => $notifiable,
                'orderItems' => $this->order->items,
            ]);
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        $itemsCount = $this->order->items->count();
        $totalAmount = $this->order->total;
        $orderNumber = $this->order->order_number ?? 'ORD-' . str_pad($this->order->id, 6, '0', STR_PAD_LEFT);

        return [
            'title' => 'Επιβεβαίωση παραγγελίας',
            'message' => "Η παραγγελία σας #{$orderNumber} έχει καταχωρηθεί με επιτυχία. Συνολικό ποσό: €{$totalAmount}",
            'order_id' => $this->order->id,
            'order_number' => $orderNumber,
            'order_status' => $this->order->status,
            'total_amount' => $totalAmount,
            'items_count' => $itemsCount,
            'action_url' => '/orders/' . $this->order->id,
            'icon' => 'shopping-bag',
            'type' => 'order_confirmation'
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