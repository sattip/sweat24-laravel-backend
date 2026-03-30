@extends('emails.layout')

@section('title', 'Κράτηση Ακυρώθηκε - Sweat93')

@section('content')
<h1>Κράτηση Ακυρώθηκε</h1>

<p>Γεια σου {{ $user->name ?? 'Φίλε Χρήστη' }},</p>

<p>Η κράτηση του μαθήματός σας ακυρώθηκε επιτυχώς. Ελπίζουμε να σας δούμε σε άλλο μάθημα σύντομα!</p>

<div class="alert alert-warning">
    <strong>Ακύρωση Επιβεβαιώθηκε:</strong> Η κράτησή σας έχει αφαιρεθεί και η θέση σας είναι τώρα διαθέσιμη για άλλα μέλη.
</div>

<div class="info-box">
    <h3>Λεπτομέρειες Ακυρωμένης Κράτησης</h3>
    <p><strong>Μάθημα:</strong> {{ $booking->gymClass->name ?? $booking->class_name ?? 'Μάθημα' }}</p>
    <p><strong>Ημερομηνία:</strong> {{ $booking->gymClass && $booking->gymClass->date ? $booking->gymClass->date->format('l, j F Y') : ($booking->date ? \Carbon\Carbon::parse($booking->date)->format('l, j F Y') : 'TBA') }}</p>
    <p><strong>Ώρα:</strong> {{ $booking->gymClass->time ?? $booking->time ?? 'TBA' }} @if($booking->gymClass && $booking->gymClass->time) - {{ \Carbon\Carbon::parse($booking->gymClass->time)->addMinutes($booking->gymClass->duration ?? 60)->format('H:i') }}@endif</p>
    <p><strong>Προπονητής:</strong> {{ $instructorName ?? ($booking->instructor ?? 'TBA') }}</p>
    <p><strong>Αριθμός Κράτησης:</strong> #{{ $booking->id }}</p>
    <p><strong>Ακυρώθηκε στις:</strong> {{ now()->format('j F Y \s\τ\ι\varsigma g:i A') }}</p>
</div>

@if($refund_info ?? false)
<div class="alert alert-success">
    <h3>Πληροφορίες Επιστροφής Χρημάτων</h3>
    <p>{{ $refund_info }}</p>
</div>
@endif

<h2>Κάντε Κράτηση σε Άλλο Μάθημα</h2>
<p>Μην αφήσετε αυτό να σας σταματήσει στο ταξίδι της φυσικής σας κατάστασης! Δείτε τα επερχόμενα μαθήματά μας και βρείτε άλλη συνεδρία που ταιριάζει στο πρόγραμμά σας.</p>

<div style="text-align: center; margin: 30px 0;">
    <a href="https://sweat93.gr/classes" class="btn">Περιήγηση Μαθημάτων</a>
    <a href="https://sweat93.gr/bookings" class="btn btn-secondary">Οι Κρατήσεις μου</a>
</div>

<h2>Δημοφιλή Μαθήματα αυτής της Εβδομάδας</h2>
<p>Εδώ είναι μερικά δημοφιλή μαθήματα που μπορεί να σας ενδιαφέρουν:</p>
<ul>
    <li>Προπόνηση Υψηλής Έντασης με Διαλείμματα (HIIT)</li>
    <li>Συνεδρίες Yoga Flow</li>
    <li>Θεμελιώδη Προπόνηση Δύναμης</li>
    <li>Μαθήματα Cardio Dance</li>
    <li>Προσωπικές Συνεδρίες EMS Προπόνησης</li>
</ul>

<div class="info-box">
    <h3>Υπενθύμιση Πολιτικής Ακύρωσης</h3>
    <p>Για μελλοντική αναφορά:</p>
    <ul style="margin: 10px 0;">
        <li>Ακύρωση μέχρι {{ config('app.cancellation_hours', 2) }} ώρες πριν το μάθημα: Χωρίς ποινή</li>
        <li>Ακύρωση λιγότερο από {{ config('app.cancellation_hours', 2) }} ώρες πριν το μάθημα: Μπορεί να επιβαρυνθείτε</li>
        <li>Οι μη εμφανίσεις μπορεί να χρεωθούν το πλήρες κόστος του μαθήματος</li>
    </ul>
</div>

<h2>Χρειάζεστε Βοήθεια;</h2>
<p>Αν ακυρώσατε κατά λάθος ή χρειάζεστε βοήθεια με επανακράτηση, παρακαλούμε επικοινωνήστε μαζί μας άμεσα:</p>
<ul>
    <li><strong>Email:</strong> <a href="mailto:info@sweat93.com">info@sweat93.com</a></li>
    <li><strong>Τηλέφωνο:</strong> <a href="tel:+302101234567">+30 210 123 4567</a></li>
</ul>

<p>Η ομάδα μας είναι εδώ για να σας βοηθήσει να διατηρήσετε τη ρουτίνα γυμναστικής σας!</p>

<p>Με εκτίμηση,<br>
Η Ομάδα του Sweat93</p>
@endsection