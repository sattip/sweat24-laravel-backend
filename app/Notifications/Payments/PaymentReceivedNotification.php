<?php

namespace App\Notifications\Payments;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentReceivedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $payment;

    /**
     * Create a new notification instance.
     */
    public function __construct(Payment $payment)
    {
        $this->payment = $payment;
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
            ->subject('Payment Received - Sweat93')
            ->view('emails.payments.payment-received', [
                'payment' => $this->payment,
                'user' => $notifiable,
                'payable' => $this->payment->payable, // Could be Order, Membership, etc.
            ]);
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        $payableType = class_basename($this->payment->payable_type);
        $payableDescription = '';
        
        switch ($payableType) {
            case 'Order':
                $payableDescription = "παραγγελία #{$this->payment->payable->order_number}";
                break;
            case 'Membership':
                $payableDescription = "συνδρομή {$this->payment->payable->name}";
                break;
            case 'Booking':
                $payableDescription = "κράτηση μαθήματος";
                break;
            default:
                $payableDescription = strtolower($payableType);
        }

        return [
            'title' => 'Πληρωμή ελήφθη',
            'message' => "Η πληρωμή σας €{$this->payment->amount} για {$payableDescription} έχει ληφθεί και επιβεβαιωθεί.",
            'payment_id' => $this->payment->id,
            'payment_method' => $this->payment->payment_method,
            'amount' => $this->payment->amount,
            'currency' => $this->payment->currency ?? 'EUR',
            'status' => $this->payment->status,
            'transaction_id' => $this->payment->transaction_id,
            'payable_type' => $payableType,
            'payable_id' => $this->payment->payable_id,
            'payable_description' => $payableDescription,
            'payment_date' => $this->payment->created_at->format('Y-m-d H:i:s'),
            'action_url' => '/payments/' . $this->payment->id,
            'icon' => 'credit-card',
            'type' => 'payment_received'
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