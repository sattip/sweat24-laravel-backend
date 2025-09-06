@extends('emails.layout')

@section('content')
<h1 style="color: #333; font-size: 24px; margin-bottom: 20px;">Νέο Αίτημα Κράτησης</h1>

<p style="color: #666; font-size: 16px; line-height: 1.5; margin-bottom: 20px;">
    Γεια σας {{ $bookingRequest->client_name ?? ($admin->first_name ?? $admin->name ?? 'User') }},
</p>

<p style="color: #666; font-size: 16px; line-height: 1.5; margin-bottom: 20px;">
    Το αίτημα κράτησής σας έχει ληφθεί και θα επεξεργαστεί σύντομα.
</p>

<div style="background-color: #f8f9fa; border-radius: 8px; padding: 20px; margin-bottom: 30px;">
    <h2 style="color: #333; font-size: 18px; margin-bottom: 15px;">Λεπτομέρειες Αιτήματος</h2>
    
    <table style="width: 100%; border-collapse: collapse;">
        @if(isset($bookingRequest->package))
        <tr>
            <td style="padding: 8px 0; color: #666; width: 40%;">Πακέτο:</td>
            <td style="padding: 8px 0; color: #333; font-weight: bold;">{{ $bookingRequest->package->name }}</td>
        </tr>
        @endif
        @if(isset($serviceTypeName))
        <tr>
            <td style="padding: 8px 0; color: #666; width: 40%;">Τύπος Υπηρεσίας:</td>
            <td style="padding: 8px 0; color: #333; font-weight: bold;">{{ $serviceTypeName }}</td>
        </tr>
        @endif
        <tr>
            <td style="padding: 8px 0; color: #666;">Προτιμώμενη Ημερομηνία:</td>
            <td style="padding: 8px 0; color: #333; font-weight: bold;">
                {{ \Carbon\Carbon::parse($bookingRequest->preferred_date)->format('d/m/Y') }}
            </td>
        </tr>
        <tr>
            <td style="padding: 8px 0; color: #666;">Προτιμώμενη Ώρα:</td>
            <td style="padding: 8px 0; color: #333; font-weight: bold;">{{ $bookingRequest->preferred_time }}</td>
        </tr>
        @if($bookingRequest->alternative_date)
        <tr>
            <td style="padding: 8px 0; color: #666;">Εναλλακτική Ημερομηνία:</td>
            <td style="padding: 8px 0; color: #333; font-weight: bold;">
                {{ \Carbon\Carbon::parse($bookingRequest->alternative_date)->format('d/m/Y') }}
            </td>
        </tr>
        <tr>
            <td style="padding: 8px 0; color: #666;">Εναλλακτική Ώρα:</td>
            <td style="padding: 8px 0; color: #333; font-weight: bold;">{{ $bookingRequest->alternative_time }}</td>
        </tr>
        @endif
        @if($bookingRequest->notes)
        <tr>
            <td style="padding: 8px 0; color: #666; vertical-align: top;">Σημειώσεις:</td>
            <td style="padding: 8px 0; color: #333;">{{ $bookingRequest->notes }}</td>
        </tr>
        @endif
    </table>
</div>

<p style="color: #666; font-size: 16px; line-height: 1.5; margin-bottom: 20px;">
    Θα επικοινωνήσουμε μαζί σας σύντομα για να επιβεβαιώσουμε το ραντεβού σας.
</p>

<div style="text-align: center; margin: 30px 0;">
    <a href="https://sweat93.gr/bookings" 
       style="display: inline-block; padding: 12px 30px; background-color: #dc3545; color: white; text-decoration: none; border-radius: 5px; font-weight: bold;">
        Δείτε τις Κρατήσεις σας
    </a>
</div>

<div style="background-color: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin-top: 30px;">
    <p style="color: #856404; font-size: 14px; margin: 0;">
        <strong>Σημείωση:</strong> Εάν δεν λάβετε επιβεβαίωση εντός 24 ωρών, παρακαλούμε επικοινωνήστε μαζί μας στο <a href="tel:+302101234567">+30 210 123 4567</a>.
    </p>
</div>
@endsection