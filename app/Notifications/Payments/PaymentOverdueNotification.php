<?php

namespace App\Notifications\Payments;

use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentOverdueNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $payment;
    protected $daysOverdue;

    /**
     * Create a new notification instance.
     */
    public function __construct(Payment $payment, int $daysOverdue = null)
    {
        $this->payment = $payment;
        $this->daysOverdue = $daysOverdue ?? Carbon::parse($payment->due_date)->diffInDays(now());
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
        $paymentUrl = config('app.url') . '/payments/' . $this->payment->id;

        return (new MailMessage)
            ->subject('Payment Overdue - Sweat93')
            ->view('emails.payments.payment-overdue', [
                'payment' => $this->payment,
                'user' => $notifiable,
                'payable' => $this->payment->payable,
                'daysOverdue' => $this->daysOverdue,
                'paymentUrl' => $paymentUrl,
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
            'title' => 'Εκπρόθεσμη πληρωμή',
            'message' => "Η πληρωμή σας €{$this->payment->amount} για {$payableDescription} είναι εκπρόθεσμη κατά {$this->daysOverdue} ημέρες. Παρακαλούμε προχωρήστε στην πληρωμή.",
            'payment_id' => $this->payment->id,
            'payment_method' => $this->payment->payment_method,
            'amount' => $this->payment->amount,
            'currency' => $this->payment->currency ?? 'EUR',
            'status' => $this->payment->status,
            'due_date' => $this->payment->due_date?->format('Y-m-d'),
            'days_overdue' => $this->daysOverdue,
            'payable_type' => $payableType,
            'payable_id' => $this->payment->payable_id,
            'payable_description' => $payableDescription,
            'late_fees' => $this->payment->late_fees ?? 0,
            'action_url' => '/payments/' . $this->payment->id,
            'icon' => 'alert-triangle',
            'type' => 'payment_overdue',
            'priority' => 'high'
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