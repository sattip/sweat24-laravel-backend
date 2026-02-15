@extends('emails.layout')

@section('content')
<h1 style="color: #333; font-size: 24px; margin-bottom: 20px;">Επιβεβαίωση Πληρωμής Δόσης</h1>

<p style="color: #666; font-size: 16px; line-height: 1.5; margin-bottom: 20px;">
    Γεια σας {{ $user->first_name ?? $user->name }},
</p>

<p style="color: #666; font-size: 16px; line-height: 1.5; margin-bottom: 20px;">
    Επιβεβαιώνουμε την παραλαβή της πληρωμής της δόσης σας.
</p>

<div style="background-color: #d4edda; border-radius: 8px; padding: 20px; margin-bottom: 30px; border-left: 4px solid #28a745;">
    <h2 style="color: #155724; font-size: 18px; margin-bottom: 15px;">✅ Πληρωμή Ληφθείσα</h2>
    
    <table style="width: 100%; border-collapse: collapse;">
        <tr>
            <td style="padding: 8px 0; color: #155724; width: 40%;">Ποσό Δόσης:</td>
            <td style="padding: 8px 0; color: #155724; font-weight: bold; font-size: 18px;">
                €{{ number_format($paymentInstallment->amount, 2) }}
            </td>
        </tr>
        <tr>
            <td style="padding: 8px 0; color: #155724;">Αριθμός Δόσης:</td>
            <td style="padding: 8px 0; color: #155724; font-weight: bold;">
                {{ $paymentInstallment->installment_number }} από {{ $paymentInstallment->total_installments }}
            </td>
        </tr>
        <tr>
            <td style="padding: 8px 0; color: #155724;">Ημερομηνία Πληρωμής:</td>
            <td style="padding: 8px 0; color: #155724; font-weight: bold;">
                {{ \Carbon\Carbon::parse($paymentInstallment->paid_at)->format('d/m/Y H:i') }}
            </td>
        </tr>
        @if($paymentInstallment->payment && $paymentInstallment->payment->transaction_id)
        <tr>
            <td style="padding: 8px 0; color: #155724;">Κωδικός Συναλλαγής:</td>
            <td style="padding: 8px 0; color: #155724; font-family: monospace;">
                {{ $paymentInstallment->payment->transaction_id }}
            </td>
        </tr>
        @endif
    </table>
</div>

@php
    $remainingInstallments = $paymentInstallment->total_installments - $paymentInstallment->installment_number;
    $remainingAmount = $remainingInstallments * $paymentInstallment->amount;
@endphp

@if($remainingInstallments > 0)
<div style="background-color: #f8f9fa; border-radius: 8px; padding: 20px; margin-bottom: 30px;">
    <h3 style="color: #333; font-size: 16px; margin-bottom: 15px;">📊 Υπόλοιπο Πληρωμών</h3>
    
    <table style="width: 100%; border-collapse: collapse;">
        <tr>
            <td style="padding: 8px 0; color: #666;">Υπολειπόμενες Δόσεις:</td>
            <td style="padding: 8px 0; color: #333; font-weight: bold;">{{ $remainingInstallments }}</td>
        </tr>
        <tr>
            <td style="padding: 8px 0; color: #666;">Υπολειπόμενο Ποσό:</td>
            <td style="padding: 8px 0; color: #333; font-weight: bold;">€{{ number_format($remainingAmount, 2) }}</td>
        </tr>
        @if(isset($nextDueDate))
        <tr>
            <td style="padding: 8px 0; color: #666;">Επόμενη Πληρωμή:</td>
            <td style="padding: 8px 0; color: #333; font-weight: bold;">
                {{ \Carbon\Carbon::parse($nextDueDate)->format('d/m/Y') }}
            </td>
        </tr>
        @endif
    </table>
</div>
@else
<div style="background-color: #d1ecf1; border-radius: 8px; padding: 20px; margin-bottom: 30px; border-left: 4px solid #17a2b8;">
    <h3 style="color: #0c5460; font-size: 16px; margin-bottom: 10px;">🎉 Συγχαρητήρια!</h3>
    <p style="color: #0c5460; font-size: 14px; margin: 0;">
        Έχετε ολοκληρώσει όλες τις δόσεις σας. Σας ευχαριστούμε για τη συνέπειά σας!
    </p>
</div>
@endif

<div style="background-color: #f8f9fa; border-radius: 8px; padding: 20px; margin-bottom: 30px;">
    <h3 style="color: #333; font-size: 16px; margin-bottom: 15px;">💳 Στοιχεία Πληρωμής</h3>
    
    <table style="width: 100%; border-collapse: collapse;">
        <tr>
            <td style="padding: 8px 0; color: #666;">Μέθοδος Πληρωμής:</td>
            <td style="padding: 8px 0; color: #333;">
                @if($paymentInstallment->payment && $paymentInstallment->payment->payment_method)
                    {{ ucfirst(str_replace('_', ' ', $paymentInstallment->payment->payment_method)) }}
                @else
                    Κάρτα
                @endif
            </td>
        </tr>
        <tr>
            <td style="padding: 8px 0; color: #666;">Κατάσταση:</td>
            <td style="padding: 8px 0; color: #28a745; font-weight: bold;">✅ Επιτυχής</td>
        </tr>
    </table>
</div>

<div style="text-align: center; margin: 30px 0;">
    <a href="{{ config('app.url') }}/payments" 
       style="display: inline-block; padding: 12px 30px; background-color: #dc3545; color: white; text-decoration: none; border-radius: 5px; font-weight: bold;">
        Δείτε το Ιστορικό Πληρωμών
    </a>
</div>

<div style="margin-top: 30px; padding: 15px; background-color: #f8f9fa; border-radius: 5px;">
    <p style="color: #666; font-size: 12px; margin: 0; text-align: center;">
        Αυτό το email αποτελεί απόδειξη πληρωμής. Παρακαλούμε κρατήστε το για τα αρχεία σας.
    </p>
</div>
@endsection