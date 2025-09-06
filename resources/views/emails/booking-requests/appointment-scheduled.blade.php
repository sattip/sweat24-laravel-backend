@extends('emails.layout')

@section('content')
<h1 style="color: #333; font-size: 24px; margin-bottom: 20px;">Το Ραντεβού σας Προγραμματίστηκε!</h1>

<p style="color: #666; font-size: 16px; line-height: 1.5; margin-bottom: 20px;">
    Γεια σας {{ $user->first_name ?? $user->name ?? ($bookingRequest->client_name ?? 'User') }},
</p>

<p style="color: #666; font-size: 16px; line-height: 1.5; margin-bottom: 20px;">
    Με χαρά σας ενημερώνουμε ότι το αίτημα κράτησής σας έχει εγκριθεί και το ραντεβού σας έχει προγραμματιστεί.
</p>

<div style="background-color: #d4edda; border-radius: 8px; padding: 20px; margin-bottom: 30px; border-left: 4px solid #28a745;">
    <h2 style="color: #155724; font-size: 18px; margin-bottom: 15px;">✅ Επιβεβαιωμένο Ραντεβού</h2>
    
    <table style="width: 100%; border-collapse: collapse;">
        @if(isset($serviceTypeName))
        <tr>
            <td style="padding: 8px 0; color: #155724; width: 40%;">Τύπος Υπηρεσίας:</td>
            <td style="padding: 8px 0; color: #155724; font-weight: bold;">{{ $serviceTypeName }}</td>
        </tr>
        @endif
        @if(isset($bookingRequest->package))
        <tr>
            <td style="padding: 8px 0; color: #155724; width: 40%;">Πακέτο:</td>
            <td style="padding: 8px 0; color: #155724; font-weight: bold;">{{ $bookingRequest->package->name }}</td>
        </tr>
        @endif
        <tr>
            <td style="padding: 8px 0; color: #155724;">Ημερομηνία:</td>
            <td style="padding: 8px 0; color: #155724; font-weight: bold;">
                {{ \Carbon\Carbon::parse($scheduledDate ?? $bookingRequest->scheduled_date ?? $bookingRequest->confirmed_date ?? now())->format('d/m/Y') }}
            </td>
        </tr>
        <tr>
            <td style="padding: 8px 0; color: #155724;">Ώρα:</td>
            <td style="padding: 8px 0; color: #155724; font-weight: bold;">
                {{ $scheduledTime ?? $bookingRequest->scheduled_time ?? $bookingRequest->confirmed_time ?? '10:00' }}
            </td>
        </tr>
        @if(isset($bookingRequest->package) && isset($bookingRequest->package->duration))
        <tr>
            <td style="padding: 8px 0; color: #155724;">Διάρκεια:</td>
            <td style="padding: 8px 0; color: #155724; font-weight: bold;">{{ $bookingRequest->package->duration }} λεπτά</td>
        </tr>
        @endif
        @if(isset($bookingRequest->package) && isset($bookingRequest->package->price))
        <tr>
            <td style="padding: 8px 0; color: #155724;">Κόστος:</td>
            <td style="padding: 8px 0; color: #155724; font-weight: bold;">€{{ number_format($bookingRequest->package->price, 2) }}</td>
        </tr>
        @endif
    </table>
</div>

<div style="background-color: #f8f9fa; border-radius: 8px; padding: 20px; margin-bottom: 30px;">
    <h3 style="color: #333; font-size: 16px; margin-bottom: 15px;">📍 Τοποθεσία</h3>
    <p style="color: #666; font-size: 14px; line-height: 1.5; margin: 0;">
        Sweat93<br>
        Ηφαίστου 4, Βάρη 16672<br>
        Τηλ: <a href="tel:+302101234567">+30 210 123 4567</a>
    </p>
</div>

<div style="text-align: center; margin: 30px 0;">
    <a href="https://sweat93.gr/bookings" 
       style="display: inline-block; padding: 12px 30px; background-color: #dc3545; color: white; text-decoration: none; border-radius: 5px; font-weight: bold;">
        Δείτε το Ραντεβού σας
    </a>
</div>

<div style="background-color: #f8f9fa; border-radius: 8px; padding: 20px; margin-top: 30px;">
    <h3 style="color: #333; font-size: 16px; margin-bottom: 15px;">⚠️ Σημαντικές Πληροφορίες</h3>
    <ul style="color: #666; font-size: 14px; line-height: 1.8; margin: 0; padding-left: 20px;">
        <li>Παρακαλούμε προσέλθετε 10 λεπτά νωρίτερα</li>
        <li>Φορέστε άνετα ρούχα</li>
        <li>Φέρτε μαζί σας πετσέτα και νερό</li>
        <li>Για ακύρωση ή αλλαγή, επικοινωνήστε τουλάχιστον 24 ώρες πριν</li>
    </ul>
</div>

<div style="margin-top: 30px; padding: 15px; background-color: #fff3cd; border-radius: 5px;">
    <p style="color: #856404; font-size: 14px; margin: 0;">
        <strong>Πολιτική Ακύρωσης:</strong> Μπορείτε να ακυρώσετε ή να αλλάξετε το ραντεβού σας έως 24 ώρες πριν. 
        Για αλλαγές, επικοινωνήστε στο <a href="tel:+302101234567">+30 210 123 4567</a>.
    </p>
</div>
@endsection