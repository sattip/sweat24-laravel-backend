@extends('emails.layout')

@section('title', 'Θέση Διαθέσιμη - Sweat93')

@section('content')
<h1>Εξαιρετικά Νέα! Μια Θέση Ελευθερώθηκε!</h1>

<p>Γεια σου {{ $user->first_name }},</p>

<p>Μια θέση ελευθερώθηκε στο μάθημα στο οποίο βρίσκεστε στη λίστα αναμονής. Έχετε περιορισμένο χρόνο να δεσμεύσετε αυτή τη θέση πριν προσφερθεί στον επόμενο άνθρωπο στη λίστα αναμονής.</p>

<div class="alert alert-success">
    <strong>Θέση Διαθέσιμη!</strong> Έχετε {{ $expires_in ?? '30 λεπτά' }} για να επιβεβαιώσετε την κράτησή σας.
</div>

<div class="info-box">
    <h3>Λεπτομέρειες Μαθήματος</h3>
    <p><strong>Μάθημα:</strong> {{ $gymClass->name }}</p>
    <p><strong>Ημερομηνία:</strong> {{ $gymClass->date->format('l, j F Y') }}</p>
    <p><strong>Ώρα:</strong> {{ $gymClass->time }} - {{ \Carbon\Carbon::parse($gymClass->time)->addMinutes($gymClass->duration ?? 60)->format('H:i') }}</p>
    <p><strong>Προπονητής:</strong> {{ $gymClass->instructor->name ?? $gymClass->instructor ?? 'TBA' }}</p>
    <p><strong>Τοποθεσία:</strong> {{ $gymClass->location ?? 'Κύριος Χώρος Γυμναστηρίου' }}</p>
    <p><strong>Διαθέσιμες Θέσεις:</strong> 1</p>
</div>

<div class="alert alert-warning">
    <strong>Ενεργήστε Γρήγορα!</strong> Αυτή η θέση θα διατεθεί αυτόματα στον επόμενο άνθρωπο στη λίστα αναμονής Εάν δεν δεσμευτεί εντός {{ $expires_in ?? '30 λεπτών' }}.
</div>

<div style="text-align: center; margin: 30px 0;">
    <a href="{{ $booking_url }}" class="btn" style="font-size: 18px; padding: 16px 40px;">Δεσμεύστε τη Θέση μου Τώρα!</a>
</div>

<div class="info-box">
    <h3>Στοιχεία Λίστας Αναμονής</h3>
    <p><strong>Μπήκατε στη Λίστα Αναμονής:</strong> {{ $waitlist_entry->created_at->format('j F Y \s\τ\ι\ς g:i A') }}</p>
    <p><strong>Η Θέση σας:</strong> Επόμενος στη σειρά</p>
    <p><strong>Σύνολο στη Λίστα Αναμονής:</strong> {{ $total_waitlisted ?? 'Πολλαπλά' }} μέλη
</div>

<h2>Τι Συμβαίνει Μετά;</h2>
<p>Μόλις δεσμεύσετε τη θέση σας:</p>
<ol>
    <li><strong>Άμεση Επιβεβαίωση:</strong> Θα λάβετε email επιβεβαίωσης κράτησης</li>
    <li><strong>Αυτόματη Αφαίρεση:</strong> Θα αφαιρεθείτε από τη λίστα αναμονής</li>
    <li><strong>Προετοιμασία Μαθήματος:</strong> Ακολουθήστε τις ίδιες οδηγίες προετοιμασίας όπως στις κανονικές κρατήσεις</li>
</ol>

<h2>Αν Δεν Μπορείτε να Παρευρεθείτε</h2>
<p>Αν δεν μπορείτε πλέον να παραβρεθείτε σε αυτό το μάθημα:</p>
<ul>
    <li>Απλώς αγνοήστε αυτό το email - η θέση θα προσφερθεί σε κάποιον άλλο</li>
    <li>Ή <a href="{{ $decline_url ?? '#' }}">κάντε κλικ εδώ για να αρνηθείτε</a> και να παραμείνετε στη λίστα αναμονής για μελλοντικά μαθήματα</li>
    <li>Θα παραμείνετε στη λίστα αναμονής για άλλες συνεδρίες αυτού του μαθήματος</li>
</ul>

@if($gymClass->description)
<h2>Σχετικά με αυτό το Μάθημα</h2>
<p>{{ $gymClass->description }}</p>
@endif

<div class="alert alert-info">
    <h3>Απαιτήσεις Μαθήματος</h3>
    <p><strong>Τι να Φέρετε:</strong> Μπουκάλι νερού, πετσέτα, και αθλητικά ρούχα</p>
    <p><strong>Άφιξη:</strong> 10 λεπτά πριν αρχίσει το μάθημα</p>
    <p><strong>Δυσκολία:</strong> Όλα τα επίπεδα είναι ευπρόσδεκτα</p>
</div>

<table class="details-table">
    <tr>
        <th>Πληροφορίες Κράτησης</th>
        <th>Λεπτομέρειες</th>
    </tr>
    <tr>
        <td>Όνομα Μέλους</td>
        <td>{{ $user->first_name }} {{ $user->last_name }}</td>
    </tr>
    <tr>
        <td>Ημερομηνία Μαθήματος</td>
        <td>{{ $gymClass->date->format('l, j F Y') }}</td>
    </tr>
    <tr>
        <td>Ώρα Μαθήματος</td>
        <td>{{ $gymClass->time }} - {{ \Carbon\Carbon::parse($gymClass->time)->addMinutes($gymClass->duration ?? 60)->format('H:i') }}</td>
    </tr>
    <tr>
        <td>Η Προσφορά Λήγει</td>
        <td>{{ now()->addMinutes(30)->format('g:i A \s\τ\ι\ς j F Y') }}</td>
    </tr>
</table>

<h2>Χρειάζεστε Βοήθεια;</h2>
<p>Αν αντιμετωπίζετε πρόβλημα με τη δέσμευση της θέσης σας ή έχετε ερωτήσεις σχετικά με το μάθημα:</p>
<ul>
    <li><strong>Email:</strong> <a href="mailto:bookings@sweat93.gr">bookings@sweat93.gr</a></li>
    <li><strong>Τηλέφωνο:</strong> <a href="tel:+306980912176">698 091 2176</a></li>
</ul>

<p>Μη χάσετε αυτή την ευκαιρία - δεσμεύστε τη θέση σας τώρα!</p>

<p>Με εκτίμηση,<br>
Η Ομάδα του Sweat93</p>
@endsection