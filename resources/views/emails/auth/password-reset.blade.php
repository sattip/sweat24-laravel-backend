@extends('emails.layout')

@section('title', 'Επαναφορά Κωδικού - Sweat93')

@section('content')
<h1>Επαναφορά Κωδικού σας</h1>

<p>Γεια σας {{ $user->first_name }},</p>

<p>Λάβαμε αίτημα για επαναφορά του κωδικού σας για τον λογαριασμό σας στο Sweat93. Αν εσείς κάνατε αυτή την αίτηση, παρακαλούμε κάντε κλικ στο παρακάτω κουμπί για να δημιουργήσετε νέο κωδικό.</p>

<div class="alert alert-info">
    <strong>Σημείωση Ασφάλειας:</strong> Αυτός ο σύνδεσμος επαναφοράς κωδικού θα λήξει σε {{ config('auth.passwords.users.expire') }} λεπτά για την ασφάλειά σας.
</div>

<div style="text-align: center; margin: 30px 0;">
    <a href="{{ $resetUrl }}" class="btn">Επαναφορά Κωδικού μου</a>
</div>

<div class="info-box">
    <h3>Λεπτομέρειες Επαναφοράς Κωδικού</h3>
    <p><strong>Λογαριασμός:</strong> {{ $user->email }}</p>
    <p><strong>Ώρα Αίτησης:</strong> {{ now()->format('j F Y \s\τ\ι\ς g:i A') }}</p>
    <p><strong>Λήγει:</strong> {{ now()->addMinutes(config('auth.passwords.users.expire'))->format('j F Y \s\τ\ι\ς g:i A') }}</p>
</div>

<h2>Δεν Ζητήσατε Αυτό;</h2>
<p>Αν δεν ζητήσατε επαναφορά κωδικού, μπορείτε να αγνοήσετε ασφαλώς αυτό το email. Ο κωδικός σας θα παραμείνει αμετάβλητος και δεν χρειάζεται καμία περαιτέρω ενέργεια.</p>

<div class="alert alert-warning">
    <strong>Συμβουλές Ασφάλειας:</strong>
    <ul style="margin: 10px 0;">
        <li>Μη μοιράζεστε ποτέ τον κωδικό σας με κανένα</li>
        <li>Χρησιμοποιήστε έναν ισχυρό, μοναδικό κωδικό για τον λογαριασμό σας</li>
        <li>Σκεφτείτε τη χρήση ενός διαχειριστή κωδικών</li>
        <li>Αποσυνδεθείτε από κοινόχρηστους ή δημόσιους υπολογιστές</li>
    </ul>
</div>

<h2>Αντιμετωπίζετε Πρόβλημα;</h2>
<p>Αν το κουμπί επαναφοράς δεν λειτουργεί, μπορείτε να αντιγράψετε και να επικολλήσετε αυτόν τον σύνδεσμο στον περιηγητή σας:</p>
<p style="word-break: break-all; background-color: #f8f9fa; padding: 10px; border-radius: 4px; font-family: monospace; font-size: 14px;">
    {{ $resetUrl }}
</p>

<p>Αν συνεχίζετε να έχετε προβλήματα με την επαναφορά του κωδικού σας, παρακαλούμε επικοινωνήστε με την ομάδα υποστήριξής μας:</p>
<ul>
    <li><strong>Email:</strong> <a href="mailto:support@sweat93.gr">support@sweat93.gr</a></li>
    <li><strong>Τηλέφωνο:</strong> <a href="tel:+306980912176">698 091 2176</a></li>
</ul>

<p>Η ομάδα υποστήριξής μας είναι διαθέσιμη Δευτέρα έως Παρασκευή, 9:00 πμ - 6:00 μμ (GMT+2).</p>

<p>Με εκτίμηση,<br>
Η Ομάδα του Sweat93</p>
@endsection