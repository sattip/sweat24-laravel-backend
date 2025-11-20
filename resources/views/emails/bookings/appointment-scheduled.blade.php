@extends('emails.layout')

@section('title', 'Ραντεβού Προγραμματίστηκε - Sweat93')

@section('content')
<h1>Το Ραντεβού Προγραμματίστηκε!</h1>

<p>Εξαιρετικά νέα, {{ $user->name ?? 'Φίλε Χρήστη' }}! Το ραντεβού σας για {{ $appointment->type === 'ems' ? 'Προπόνηση EMS' : 'Προσωπική Προπόνηση' }} έχει προγραμματιστεί.</p>

<div class="alert alert-success">
    <strong>Το ραντεβού σας επιβεβαιώθηκε!</strong> Παρακαλούμε φτάστε 10 λεπτά νωρίτερα για την προετοιμασία της συνεδρίας σας.
</div>

<div class="info-box">
    <h3>Λεπτομέρειες Ραντεβού</h3>
    <p><strong>Υπηρεσία:</strong> {{ $appointment->type === 'ems' ? 'Συνεδρία Προπόνησης EMS' : 'Συνεδρία Προσωπικής Προπόνησης' }}</p>
    <p><strong>Προπονητής:</strong> {{ $appointment->trainer ? ($appointment->trainer->name ?? 'TBA') : 'TBA' }}</p>
    <p><strong>Ημερομηνία:</strong> {{ $appointment->scheduled_at ? $appointment->scheduled_at->format('l, j F Y') : 'TBA' }}</p>
    <p><strong>Ώρα:</strong> @if($appointment->scheduled_at){{ $appointment->scheduled_at->format('g:i A') }} @if($appointment->duration)- {{ $appointment->scheduled_at->addMinutes($appointment->duration)->format('g:i A') }}@endif @else TBA @endif</p>
    <p><strong>Διάρκεια:</strong> {{ $appointment->duration }} λεπτά</p>
    <p><strong>Τοποθεσία:</strong> {{ $appointment->room ?? 'Αίθουσα Προσωπικής Προπόνησης' }}</p>
    <p><strong>ID Ραντεβού:</strong> #{{ $appointment->id }}</p>
</div>

@if($appointment->type === 'ems')
<h2>Σχετικά με την Προπόνηση EMS</h2>
<p>Η προπόνηση με Ηλεκτρική Διέγερση Μυών (EMS) είναι μια εξαιρετικά αποτελεσματική μέθοδος προπόνησης που χρησιμοποιεί ηλεκτρικές διέγερση για να διεγείρει τις μυϊκές συστολές, παρέχοντας μια πλήρη προπόνηση σώματος σε μόλις 20 λεπτά.</p>

<div class="alert alert-info">
    <h3>Απαιτήσεις Συνεδρίας EMS</h3>
    <ul style="margin: 10px 0;">
        <li>Φορέστε άνετα, εφαρμοστά ρούχα</li>
        <li>Αποφύγετε τη λήψη βαρύ φαγητού 2 ώρες πριν τη συνεδρία σας</li>
        <li>Φέρτε μπουκάλι νερού για να παραμείνετε υδατωμένοι</li>
        <li>Ενημερώστε τον προπονητή σας για τυχόν ιατρικές καταστάσεις</li>
    </ul>
</div>
@else
<h2>Σχετικά με την Προσωπική Προπόνηση</h2>
<p>Η συνεδρία προσωπικής προπόνησής σας είναι προσαρμοσμένη στους στόχους γυμναστικής και το επίπεδο εμπειρίας σας. Ο προπονητής σας θα σας καθοδηγήσει σε ασκήσεις σχεδιασμένες να μεγιστοποιήσουν τα αποτελέσματά σας με ασφάλεια και αποτελεσματικότητα.</p>

<div class="alert alert-info">
    <h3>Συμβουλές για τη Συνεδρία Προσωπικής Προπόνησης</h3>
    <ul style="margin: 10px 0;">
        <li>Φορέστε αθλητικά ρούχα και κατάλληλα παπούτσια</li>
        <li>Φέρτε μπουκάλι νερού και πετσέτα</li>
        <li>Έρθετε έτοιμοι να συζητήσετε τους στόχους γυμναστικής σας</li>
        <li>Φτάστε αναπαυμένοι και έτοιμοι για προπόνηση</li>
    </ul>
</div>
@endif

<h2>Γνωρίστε τον Προπονητή σας</h2>
@if($appointment->trainer->bio)
<div class="info-box">
    <h3>{{ $appointment->trainer->first_name }} {{ $appointment->trainer->last_name }}</h3>
    <p>{{ $appointment->trainer->bio }}</p>
    @if($appointment->trainer->specializations)
    <p><strong>Ειδικότητες:</strong> {{ $appointment->trainer->specializations }}</p>
    @endif
</div>
@else
<p><strong>{{ $appointment->trainer->first_name }} {{ $appointment->trainer->last_name }}</strong> θα είναι ο προπονητής σας για αυτή τη συνεδρία. Είναι ενθουσιασμένος να σας βοηθήσει να επιτύχετε τους στόχους γυμναστικής σας!</p>
@endif

@if($appointment->notes)
<div class="alert alert-warning">
    <h3>Ειδικές Σημειώσεις</h3>
    <p>{{ $appointment->notes }}</p>
</div>
@endif

<h2>Λίστα Προετοιμασίας</h2>
<ul>
    <li>Φτάστε 10 λεπτά νωρίτερα για check-in</li>
    <li>Συμπληρώστε ερωτηματολόγιο υγείας Εάν είναι η πρώτη σας επίσκεψη</li>
    <li>Συζητήστε τους στόχους και τις προσδοκίες σας με τον προπονητή</li>
    <li>Παραμείνετε υδατωμένοι πριν και κατά τη διάρκεια της συνεδρίας</li>
    <li>Ενημερώστε τον προπονητή για τυχόν τραυματισμούς ή περιορισμούς</li>
</ul>

<h2>Πολιτική Ακύρωσης</h2>
<p>Παρακαλούμε σημειώστε ότι τα ραντεβού {{ $appointment->type === 'ems' ? 'EMS' : 'προσωπικής προπόνησης' }} απαιτούν τουλάχιστον 24 ώρες προειδοποίηση για ακύρωση ώστε να αποφευχθούν χρεώσεις.</p>

<div style="text-align: center; margin: 30px 0;">
    <a href="{{ config('app.url') }}/appointments/{{ $appointment->id }}" class="btn">Προβολή Ραντεβού</a>
    <a href="{{ config('app.url') }}/appointments/{{ $appointment->id }}/reschedule" class="btn btn-secondary">Αναπρογραμματισμός</a>
</div>

<table class="details-table">
    <tr>
        <th>Περίληψη Ραντεβού</th>
        <th>Λεπτομέρειες</th>
    </tr>
    <tr>
        <td>Όνομα Πελάτη</td>
        <td>{{ $user->first_name }} {{ $user->last_name }}</td>
    </tr>
    <tr>
        <td>Επικοινωνία</td>
        <td>{{ $user->email }}<br>{{ $user->phone ?? 'Δεν δόθηκε' }}</td>
    </tr>
    <tr>
        <td>Κρατήθηκε στις</td>
        <td>{{ $appointment->created_at->format('j F Y \s\τ\ι\ς g:i A') }}</td>
    </tr>
    <tr>
        <td>Κατάσταση</td>
        <td><span style="color: #28a745; font-weight: 600;">Προγραμματισμένο</span></td>
    </tr>
</table>

<h2>Ερωτήσεις ή Ανησυχίες;</h2>
<p>Αν έχετε ερωτήσεις σχετικά με το ραντεβού σας ή χρειάζεστε να κάνετε αλλαγές, παρακαλούμε επικοινωνήστε μαζί μας:</p>
<ul>
    <li><strong>Email:</strong> <a href="mailto:appointments@sweat93.gr">appointments@sweat93.gr</a></li>
    <li><strong>Τηλέφωνο:</strong> <a href="tel:+306980912176">698 091 2176</a></li>
    <li><strong>Απευθείας στον Προπονητή:</strong> Διαθέσιμο μέσω της πύλης μελών</li>
</ul>

<p>Είμαστε ενθουσιασμένοι να σας βοηθήσουμε να επιτύχετε τους στόχους γυμναστικής σας!</p>

<p>Με εκτίμηση,<br>
Η Ομάδα του Sweat93</p>
@endsection