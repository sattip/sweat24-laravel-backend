<?php

namespace App\Notifications\Payments;

use App\Models\PaymentInstallment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentInstallmentReceivedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $paymentInstallment;

    /**
     * Create a new notification instance.
     */
    public function __construct(PaymentInstallment $paymentInstallment)
    {
        $this->paymentInstallment = $paymentInstallment;
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
            ->subject('Payment Installment Received - Sweat93')
            ->view('emails.payments.payment-installment-received', [
                'paymentInstallment' => $this->paymentInstallment,
                'user' => $notifiable,
            ]);
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Δόση πληρωμής ελήφθη',
            'message' => "Η δόση σας #{$this->paymentInstallment->installment_number}/{$this->paymentInstallment->total_installments} για το πακέτο {$this->paymentInstallment->package_name} έχει ληφθεί επιτυχώς. Ποσό: €{$this->paymentInstallment->amount}",
            'payment_installment_id' => $this->paymentInstallment->id,
            'customer_name' => $this->paymentInstallment->customer_name,
            'package_name' => $this->paymentInstallment->package_name,
            'installment_number' => $this->paymentInstallment->installment_number,
            'total_installments' => $this->paymentInstallment->total_installments,
            'amount' => $this->paymentInstallment->amount,
            'payment_method' => $this->paymentInstallment->payment_method,
            'due_date' => $this->paymentInstallment->due_date,
            'paid_date' => $this->paymentInstallment->paid_date,
            'status' => $this->paymentInstallment->status,
            'action_url' => '/payment-installments/' . $this->paymentInstallment->id,
            'icon' => 'credit-card',
            'type' => 'payment_installment_received'
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