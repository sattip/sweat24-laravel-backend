@extends('emails.layout')

@section('title', 'Κράτηση Επιβεβαιώθηκε - Sweat93')

@section('content')
<h1>Η Κράτηση Επιβεβαιώθηκε!</h1>

<p>Εξαιρετικά νέα, {{ $user->name ?? 'Φίλε Χρήστη' }}! Η κράτηση του μαθήματός σας έχει επιβεβαιωθεί. Είμαστε ενθουσιασμένοι να σας δούμε στο γυμναστήριο!</p>

<div class="alert alert-success">
    <strong>Η θέση σας έχει δεσμευτεί!</strong> Παρακαλούμε φτάστε 10 λεπτά νωρίτερα για check-in και προετοιμασία εξοπλισμού.
</div>

<div class="info-box">
    <h3>Λεπτομέρειες Κράτησης</h3>
    <p><strong>Μάθημα:</strong> {{ $booking->gymClass->name ?? $booking->class_name ?? 'Μάθημα' }}</p>
    <p><strong>Ημερομηνία:</strong> {{ $booking->gymClass && $booking->gymClass->date ? $booking->gymClass->date->format('l, j F Y') : ($booking->date ? \Carbon\Carbon::parse($booking->date)->format('l, j F Y') : 'TBA') }}</p>
    <p><strong>Ώρα:</strong> {{ $booking->gymClass->time ?? $booking->time ?? 'TBA' }} @if($booking->gymClass && $booking->gymClass->time) - {{ \Carbon\Carbon::parse($booking->gymClass->time)->addMinutes($booking->gymClass->duration ?? 60)->format('H:i') }}@endif</p>
    <p><strong>Προπονητής:</strong> {{ $instructorName ?? ($booking->instructor ?? 'TBA') }}</p>
    <p><strong>Τοποθεσία:</strong> {{ $booking->gymClass->location ?? $booking->location ?? 'Κύριος Χώρος Γυμναστηρίου' }}</p>
    <p><strong>Αριθμός Κράτησης:</strong> #{{ $booking->id }}</p>
</div>

@if($booking->gymClass && $booking->gymClass->description)
<h2>Σχετικά με αυτό το Μάθημα</h2>
<p>{{ $booking->gymClass->description }}</p>
@endif

<h2>Πριν την Άφιξή σας</h2>
<ul>
    <li><strong>Φτάστε Νωρίς:</strong> Παρακαλούμε φτάστε 10 λεπτά πριν αρχίσει το μάθημα</li>
    <li><strong>Τι να Φοράτε:</strong> Άνετα ρούχα γυμναστικής και αθλητικά παπούτσια</li>
    <li><strong>Τι να Φέρετε:</strong> Μπουκάλι νερού και πετσέτα (εξοπλισμός παρέχεται)</li>
    <li><strong>Έλεγχος Υγείας:</strong> Παρακαλούμε μην παραβρεθείτε Εάν δεν αισθάνεστε καλά</li>
</ul>

<h2>Πολιτική Ακύρωσης</h2>
<p>Μπορείτε να ακυρώσετε την κράτησή σας μέχρι {{ config('app.cancellation_hours', 2) }} ώρες πριν αρχίσει το μάθημα χωρίς ποινή.</p>

<div style="text-align: center; margin: 30px 0;">
    <a href="{{ config('app.url') }}/bookings/{{ $booking->id }}" class="btn">Προβολή Λεπτομερειών Κράτησης</a>
    <a href="{{ config('app.url') }}/bookings/{{ $booking->id }}/cancel" class="btn btn-secondary">Ακύρωση Κράτησης</a>
</div>

<table class="details-table">
    <tr>
        <th>Περίληψη Κράτησης</th>
        <th>Λεπτομέρειες</th>
    </tr>
    <tr>
        <td>Όνομα Μέλους</td>
        <td>{{ $user->name ?? 'Χρήστης' }}</td>
    </tr>
    <tr>
        <td>Email</td>
        <td>{{ $user->email }}</td>
    </tr>
    <tr>
        <td>Ημερομηνία Κράτησης</td>
        <td>{{ $booking->created_at ? $booking->created_at->format('j F Y \s\τ\ι\ς g:i A') : now()->format('j F Y \s\τ\ι\ς g:i A') }}</td>
    </tr>
    <tr>
        <td>Κατάσταση</td>
        <td><span style="color: #28a745; font-weight: 600;">Επιβεβαιωμένη</span></td>
    </tr>
</table>

<h2>Χρειάζεστε Βοήθεια;</h2>
<p>Αν έχετε ερωτήσεις σχετικά με την κράτησή σας ή το μάθημα, παρακαλούμε επικοινωνήστε μαζί μας:</p>
<ul>
    <li><strong>Email:</strong> <a href="mailto:bookings@sweat93.gr">bookings@sweat93.gr</a></li>
    <li><strong>Τηλέφωνο:</strong> <a href="tel:+306980912176">698 091 2176</a></li>
</ul>

<p>Ανυπομονούμε να σας δούμε στο μάθημα!</p>

<p>Με εκτίμηση,<br>
Η Ομάδα του Sweat93</p>
@endsection