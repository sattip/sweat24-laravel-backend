@extends('emails.layout')

@section('title', 'Νέα Εγγραφή Χρήστη - Sweat93 Admin')

@section('content')
<h1>Νέα Εγγραφή Χρήστη</h1>

<p>Ένας νέος χρήστης έχει εγγραφεί στη Sweat93 και αναμένει έγκριση.</p>

<div class="alert alert-info">
    <strong>Απαιτείται Ενέργεια:</strong> Παρακαλώ εξετάστε και εγκρίνετε ή απορρίψτε αυτή την εγγραφή στο admin panel.
</div>

<div class="info-box">
    <h3>Στοιχεία Εγγραφής Χρήστη</h3>
    <p><strong>Όνομα:</strong> {{ $user->first_name }} {{ $user->last_name }}</p>
    <p><strong>Email:</strong> {{ $user->email }}</p>
    <p><strong>Τηλέφωνο:</strong> {{ $user->phone ?? 'Δεν είναι διαθέσιμο' }}</p>
    <p><strong>Ημερομηνία Γέννησης:</strong> {{ $user->date_of_birth ? $user->date_of_birth->format('F j, Y') : 'Δεν είναι διαθέσιμη' }}</p>
    <p><strong>Φύλο:</strong> {{ $user->gender ? ucfirst($user->gender) : 'Δεν είναι καθορισμένο' }}</p>
    <p><strong>Ημερομηνία Εγγραφής:</strong> {{ $user->created_at->format('F j, Y \a\t g:i A') }}</p>
</div>

@if($user->emergency_contact_name || $user->emergency_contact_phone)
<div class="info-box">
    <h3>Επικοινωνία Εκτάκτου Ανάγκης</h3>
    @if($user->emergency_contact_name)
    <p><strong>Όνομα:</strong> {{ $user->emergency_contact_name }}</p>
    @endif
    @if($user->emergency_contact_phone)
    <p><strong>Τηλέφωνο:</strong> {{ $user->emergency_contact_phone }}</p>
    @endif
    @if($user->emergency_contact_relationship)
    <p><strong>Σχέση:</strong> {{ $user->emergency_contact_relationship }}</p>
    @endif
</div>
@endif

@if($user->fitness_goals || $user->health_conditions || $user->experience_level)
<div class="info-box">
    <h3>Πληροφορίες Φίτνες</h3>
    @if($user->fitness_goals)
    <p><strong>Στόχοι Φίτνες:</strong> {{ $user->fitness_goals }}</p>
    @endif
    @if($user->experience_level)
    <p><strong>Επίπεδο Εμπειρίας:</strong> {{ ucfirst($user->experience_level) }}</p>
    @endif
    @if($user->health_conditions)
    <p><strong>Προβλήματα Υγείας:</strong> {{ $user->health_conditions }}</p>
    @endif
</div>
@endif

@if($user->how_did_you_hear || $user->referral_code)
<div class="info-box">
    <h3>Πληροφορίες Marketing</h3>
    @if($user->how_did_you_hear)
    <p><strong>Πώς μας άκουσε:</strong> {{ $user->how_did_you_hear }}</p>
    @endif
    @if($user->referral_code)
    <p><strong>Κωδικός Referral:</strong> {{ $user->referral_code }}</p>
    @endif
</div>
@endif

<h2>Περίληψη Προφίλ Χρήστη</h2>
<table class="details-table">
    <tr>
        <th>Πεδίο</th>
        <th>Αξία</th>
    </tr>
    <tr>
        <td>Πλήρες Όνομα</td>
        <td>{{ $user->first_name }} {{ $user->last_name }}</td>
    </tr>
    <tr>
        <td>Διεύθυνση Email</td>
        <td>{{ $user->email }}</td>
    </tr>
    <tr>
        <td>Αριθμός Τηλεφώνου</td>
        <td>{{ $user->phone ?? 'Δεν είναι διαθέσιμο' }}</td>
    </tr>
    <tr>
        <td>Κατάσταση Λογαριασμού</td>
        <td><span style="color: #ffc107; font-weight: 600;">Εκκρεμής Έγκριση</span></td>
    </tr>
    <tr>
        <td>Διεύθυνση IP</td>
        <td>{{ $user->registration_ip ?? 'Δεν ανιχνεύεται' }}</td>
    </tr>
    <tr>
        <td>Πηγή Εγγραφής</td>
        <td>{{ $user->registration_source ?? 'Website' }}</td>
    </tr>
</table>

<h2>Ελέγχοι Επιβεβαίωσης</h2>
<div class="info-box">
    <h3>Αυτόματοι Έλεγχοι</h3>
    <p><strong>Επικύρωση Email:</strong> <span style="color: #28a745;">✓ Έγκυρο</span></p>
    <p><strong>Επικύρωση Τηλεφώνου:</strong> {{ $user->phone_verified_at ? '✓ Επιβεβαιωμένο' : '⚠️ Δεν επιβεβαιώθηκε' }}</p>
    <p><strong>Έλεγχος Διπλότυπου:</strong> {{ $user->is_duplicate ? '⚠️ Εντοπίστηκε πιθανός διπλότυπος' : '✓ Δεν βρέθηκαν διπλότυπα' }}</p>
    <p><strong>Έλεγχος Blacklist:</strong> {{ $user->is_blacklisted ? '❌ Σε blacklist' : '✓ Καθαρό' }}</p>
</div>

@if($user->notes)
<div class="alert alert-warning">
    <h3>Σημειώσεις Εγγραφής</h3>
    <p>{{ $user->notes }}</p>
</div>
@endif

<h2>Απαιτούμενες Ενέργειες</h2>
<p>Παρακαλώ εξετάστε αυτή την εγγραφή και αναλάβετε μία από τις ακόλουθες ενέργειες:</p>

<div style="text-align: center; margin: 30px 0;">
    <a href="https://panel.sweat93.gr/admin/users/{{ $user->id }}/approve" class="btn" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">Έγκριση Εγγραφής</a>
    <a href="https://panel.sweat93.gr/admin/users/{{ $user->id }}/reject" class="btn" style="background: linear-gradient(135deg, #dc3545 0%, #fd7e14 100%);">Aπόρριψη Εγγραφής</a>
    <a href="https://panel.sweat93.gr/admin/users/{{ $user->id }}" class="btn btn-secondary">Προβολή Πλήρους Προφίλ</a>
</div>

<h2>Οδηγίες Έγκρισης</h2>
<p><strong>Εγκρίνετε εάν:</strong></p>
<ul>
    <li>Όλες οι απαιτούμενες πληροφορίες έχουν δοθεί</li>
    <li>Τα στοιχεία επικοινωνίας φαίνονται νόμιμα</li>
    <li>Δεν υπάρχουν red flags στις πληροφορίες προφίλ</li>
    <li>Περνά τους αυτόματους ελέγχους επιβεβαίωσης</li>
</ul>

<p><strong>Εξετάστε περαιτέρω εάν:</strong></p>
<ul>
    <li>Λείπουν κρίσιμες πληροφορίες</li>
    <li>Υποψιάζεστε στοιχεία επικοινωνίας</li>
    <li>Αποτύχανε στους ελέγχους επιβεβαίωσης</li>
    <li>Επισημάνθηκε από αυτόματα συστήματα</li>
</ul>

<h2>Στοιχεία Επικοινωνίας</h2>
<div class="info-box">
    <h3>Μέθοδοι Επικοινωνίας Χρήστη</h3>
    <p><strong>Κύριο Email:</strong> <a href="mailto:{{ $user->email }}">{{ $user->email }}</a></p>
    @if($user->phone)
    <p><strong>Τηλέφωνο:</strong> <a href="tel:{{ $user->phone }}">{{ $user->phone }}</a></p>
    @endif
    <p><strong>Προτιμώμενη Επικοινωνία:</strong> {{ $user->preferred_contact ?? 'Email' }}</p>
</div>

<h2>Στατιστικά Εγγραφών</h2>
<div class="info-box">
    <h3>Πρόσφατη Δραστηριότητα</h3>
    <p><strong>Εγγραφές Σήμερα:</strong> {{ $stats['today'] ?? 0 }}</p>
    <p><strong>Εκκρεμείς Εγκρίσεις:</strong> {{ $stats['pending'] ?? 0 }}</p>
    <p><strong>Αυτόν τον Μήνα:</strong> {{ $stats['month'] ?? 0 }} νέες εγγραφές</p>
</div>

<p>Παρακαλώ επεξεργαστείτε αυτή την εγγραφή εντός 24 ωρών για να διατηρήσετε καλή user experience.</p>

<div class="alert alert-info">
    <p><strong>Υπενθύμιση:</strong> Ο χρήστης θα λάβει αυτόματη ειδοποίηση email όταν εγκρίνετε ή απορρίψετε την εγγραφή τους.</p>
</div>

<p>Admin Panel: <a href="https://panel.sweat93.gr/admin">https://panel.sweat93.gr/admin</a></p>
@endsection