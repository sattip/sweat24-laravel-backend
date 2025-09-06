@extends('emails.layout')

@section('title', 'Νέο Booking Request - Sweat93 Admin')

@section('content')
<h1>Νέο {{ $booking->type === 'ems' ? 'EMS Training' : 'Personal Training' }} Request</h1>

<p>Ένα νέο {{ $booking->type === 'ems' ? 'EMS training' : 'personal training' }} session έχει ζητηθεί και απαιτεί επιβεβαίωση προγραμματισμού.</p>

<div class="alert alert-info">
    <strong>Απαιτείται Ενέργεια:</strong> Παρακαλώ εξετάστε και επιβεβαιώστε αυτό το booking request στο admin panel.
</div>

<div class="info-box">
    <h3>Στοιχεία Booking Request</h3>
    <p><strong>Τύπος Υπηρεσίας:</strong> {{ $booking->type === 'ems' ? 'EMS Training Session' : 'Personal Training Session' }}</p>
    <p><strong>Request ID:</strong> #{{ $booking->id }}</p>
    <p><strong>Πελάτης:</strong> {{ $booking->user->first_name }} {{ $booking->user->last_name }}</p>
    <p><strong>Ζητούμενη Ημερομηνία:</strong> {{ $booking->requested_date->format('l, F j, Y') }}</p>
    <p><strong>Προτιμώμενη Ώρα:</strong> {{ $booking->preferred_time ?? 'Ευέλικτη' }}</p>
    <p><strong>Διάρκεια:</strong> {{ $booking->duration ?? '60' }} λεπτά</p>
    <p><strong>Ημερομηνία Request:</strong> {{ $booking->created_at->format('F j, Y \a\t g:i A') }}</p>
    <p><strong>Κατάσταση:</strong> <span style="color: #ffc107; font-weight: 600;">Εκκρεμής Επιβεβαίωση</span></p>
</div>

<div class="info-box">
    <h3>Στοιχεία Πελάτη</h3>
    <p><strong>Όνομα:</strong> {{ $booking->user->first_name }} {{ $booking->user->last_name }}</p>
    <p><strong>Email:</strong> {{ $booking->user->email }}</p>
    <p><strong>Τηλέφωνο:</strong> {{ $booking->user->phone ?? 'Δεν είναι διαθέσιμο' }}</p>
    <p><strong>Μέλος από:</strong> {{ $booking->user->created_at->format('F j, Y') }}</p>
    @if($booking->user->experience_level)
    <p><strong>Επίπεδο Εμπειρίας:</strong> {{ ucfirst($booking->user->experience_level) }}</p>
    @endif
</div>

@if($booking->preferred_trainer_id)
<div class="info-box">
    <h3>Προτίμηση Trainer</h3>
    <p><strong>Προτιμώμενος Trainer:</strong> {{ $booking->preferredTrainer->first_name }} {{ $booking->preferredTrainer->last_name }}</p>
    @if($booking->trainer_reason)
    <p><strong>Λόγος:</strong> {{ $booking->trainer_reason }}</p>
    @endif
</div>
@endif

@if($booking->goals || $booking->focus_areas)
<div class="info-box">
    <h3>Στόχοι & Focus Session</h3>
    @if($booking->goals)
    <p><strong>Στόχοι:</strong> {{ $booking->goals }}</p>
    @endif
    @if($booking->focus_areas)
    <p><strong>Περιοχές Focus:</strong> {{ $booking->focus_areas }}</p>
    @endif
</div>
@endif

@if($booking->health_conditions || $booking->injuries)
<div class="alert alert-warning">
    <h3>Πληροφορίες Υγείας & Ασφάλειας</h3>
    @if($booking->health_conditions)
    <p><strong>Προβλήματα Υγείας:</strong> {{ $booking->health_conditions }}</p>
    @endif
    @if($booking->injuries)
    <p><strong>Τρέχοντες Τραυματισμοί:</strong> {{ $booking->injuries }}</p>
    @endif
    @if($booking->medications)
    <p><strong>Φάρμακα:</strong> {{ $booking->medications }}</p>
    @endif
</div>
@endif

@if($booking->special_requests)
<div class="info-box">
    <h3>Ειδικές Αιτήσεις</h3>
    <p>{{ $booking->special_requests }}</p>
</div>
@endif

<h2>{{ $booking->type === 'ems' ? 'EMS Training' : 'Personal Training' }} Στοιχεία</h2>

@if($booking->type === 'ems')
<div class="info-box">
    <h3>Απαιτήσεις EMS Session</h3>
    <p><strong>Προηγούμενη EMS Εμπειρία:</strong> {{ $booking->ems_experience ?? 'Δεν καθορίστηκε' }}</p>
    @if($booking->ems_concerns)
    <p><strong>EMS Ανησυχίες:</strong> {{ $booking->ems_concerns }}</p>
    @endif
    <p><strong>Επίπεδο Εντάσεως:</strong> {{ $booking->intensity_preference ?? 'Μέτριο' }}</p>
</div>
@else
<div class="info-box">
    <h3>Προτιμήσεις Personal Training</h3>
    <p><strong>Στυλ Προπόνησης:</strong> {{ $booking->training_style ?? 'Δεν καθορίστηκε' }}</p>
    @if($booking->equipment_preference)
    <p><strong>Προτίμηση Εξοπλισμού:</strong> {{ $booking->equipment_preference }}</p>
    @endif
    <p><strong>Εντάση Άσκησης:</strong> {{ $booking->intensity_preference ?? 'Μέτρια' }}</p>
</div>
@endif

<h2>Επιλογές Προγραμματισμού</h2>
<table class="details-table">
    <tr>
        <th>Επιλογή</th>
        <th>Στοιχεία</th>
    </tr>
    <tr>
        <td>Κύρια Ημερομηνία/Ώρα</td>
        <td>{{ $booking->requested_date->format('l, F j, Y') }} at {{ $booking->preferred_time ?? 'Ευέλικτη χρονική στιγμή' }}</td>
    </tr>
    @if($booking->alternative_date_1)
    <tr>
        <td>Εναλλακτική 1</td>
        <td>{{ $booking->alternative_date_1->format('l, F j, Y') }} at {{ $booking->alternative_time_1 ?? 'Ευέλικτη χρονική στιγμή' }}</td>
    </tr>
    @endif
    @if($booking->alternative_date_2)
    <tr>
        <td>Εναλλακτική 2</td>
        <td>{{ $booking->alternative_date_2->format('l, F j, Y') }} at {{ $booking->alternative_time_2 ?? 'Ευέλικτη χρονική στιγμή' }}</td>
    </tr>
    @endif
    <tr>
        <td>Διάρκεια Session</td>
        <td>{{ $booking->duration ?? '60' }} λεπτά</td>
    </tr>
    <tr>
        <td>Επαναλαμβανόμενα Sessions</td>
        <td>{{ $booking->is_recurring ? 'Ναι - ' . ($booking->recurring_frequency ?? 'Εβδομαδιαία') : 'Μεμονωμένο session' }}</td>
    </tr>
</table>

<h2>Διαθέσιμοι Trainers</h2>
@if($available_trainers ?? false)
<div class="info-box">
    <h3>Trainers Διαθέσιμοι για {{ $booking->requested_date->format('F j, Y') }}</h3>
    @foreach($available_trainers as $trainer)
    <p><strong>{{ $trainer->first_name }} {{ $trainer->last_name }}</strong>
       @if($trainer->specializations)
       - {{ $trainer->specializations }}
       @endif
    </p>
    @endforeach
</div>
@endif

<h2>Προτεραιότητα Booking</h2>
<div class="alert {{ $booking->priority === 'high' ? 'alert-danger' : ($booking->priority === 'medium' ? 'alert-warning' : 'alert-info') }}">
    <h3>Επίπεδο Προτεραιότητας: {{ ucfirst($booking->priority ?? 'κανονική') }}</h3>
    @if($booking->priority === 'high')
    <p>Αυτό το booking απαιτεί άμεση προσοχή - VIP πελάτης ή επείγουσα αίτηση.</p>
    @elseif($booking->priority === 'medium')
    <p>Αυτό το booking θα πρέπει να επεξεργαστεί εντός 4 ωρών.</p>
    @else
    <p>Κανονικό booking - επεξεργασία εντός 24 ωρών.</p>
    @endif
</div>

<h2>Απαιτούμενες Ενέργειες</h2>
<p>Παρακαλώ εξετάστε αυτό το booking request και αναλάβετε την κατάλληλη ενέργεια:</p>

<div style="text-align: center; margin: 30px 0;">
    <a href="https://panel.sweat93.gr/admin/bookings/{{ $booking->id }}/schedule" class="btn">Προγραμμάτισε Session</a>
    <a href="https://panel.sweat93.gr/admin/bookings/{{ $booking->id }}/assign-trainer" class="btn" style="background: linear-gradient(135deg, #17a2b8 0%, #20c997 100%);">Assign Trainer</a>
    <a href="https://panel.sweat93.gr/admin/bookings/{{ $booking->id }}" class="btn btn-secondary">Προβολή Πλήρων Στοιχείων</a>
</div>

<h2>Επικοινωνία με τον Πελάτη</h2>
<div class="info-box">
    <h3>Στοιχεία Επικοινωνίας Πελάτη</h3>
    <p><strong>Email:</strong> <a href="mailto:{{ $booking->user->email }}?subject=Your {{ $booking->type === 'ems' ? 'EMS Training' : 'Personal Training' }} Request">{{ $booking->user->email }}</a></p>
    @if($booking->user->phone)
    <p><strong>Τηλέφωνο:</strong> <a href="tel:{{ $booking->user->phone }}">{{ $booking->user->phone }}</a></p>
    @endif
    <p><strong>Προτιμώμενη Επικοινωνία:</strong> {{ $booking->preferred_contact ?? $booking->user->preferred_contact ?? 'Email' }}</p>
</div>

<h2>Οδηγίες Booking</h2>
<div class="info-box">
    <h3>Checklist Επεξεργασίας</h3>
    <ul style="margin: 10px 0;">
        <li>Εξετάστε προβλήματα υγείας και τραυματισμούς</li>
        <li>Ελέγξτε διαθεσιμότητα trainer και ειδικότητες</li>
        <li>Επιβεβαιώστε διαθεσιμότητα εξοπλισμού/αίθουσας</li>
        <li>Αναθέστε κατάλληλο trainer βάσει προτιμήσεων</li>
        <li>Προγραμματίστε session εντός του ζητούμενου χρονικού πλαισίου</li>
        <li>Στείλτε επιβεβαίωση στον πελάτη</li>
    </ul>
</div>

@if($booking->payment_status === 'pending')
<div class="alert alert-warning">
    <h3>Κατάσταση Πληρωμής</h3>
    <p><strong>Πληρωμή:</strong> {{ ucfirst($booking->payment_status) }}</p>
    <p>Τέλος session: €{{ number_format($booking->session_fee ?? ($booking->type === 'ems' ? 45 : 35), 2) }}</p>
    <p>Η πληρωμή θα επεξεργαστεί πριν την επιβεβαίωση session.</p>
</div>
@endif

<h2>Πρόσφατη Δραστηριότητα</h2>
<div class="info-box">
    <h3>Admin Στατιστικά</h3>
    <p><strong>Εκκρεμείς {{ $booking->type === 'ems' ? 'EMS' : 'PT' }} Αιτήσεις:</strong> {{ $stats['pending'] ?? 0 }}</p>
    <p><strong>Αιτήσεις Σήμερα:</strong> {{ $stats['today'] ?? 0 }}</p>
    <p><strong>Αυτή την Εβδομάδα:</strong> {{ $stats['week'] ?? 0 }} νέες αιτήσεις</p>
</div>

<p>Παρακαλώ επεξεργαστείτε αυτό το booking request άμεσα για να διατηρήσετε την ικανοποίηση του πελάτη.</p>

<p>Admin Panel: <a href="https://panel.sweat93.gr/admin/bookings">https://panel.sweat93.gr/admin/bookings</a></p>
@endsection