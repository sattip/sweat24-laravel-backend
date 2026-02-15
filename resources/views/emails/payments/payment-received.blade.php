@extends('emails.layout')

@section('title', 'Πληρωμή Παρελήφθη - Sweat93')

@section('content')
<h1>Πληρωμή Παρελήφθη!</h1>

<p>Σας ευχαριστούμε, {{ $user->first_name }}! Λάβαμε με επιτυχία την πληρωμή σας.</p>

<div class="alert alert-success">
    <strong>Πληρωμή Επιβεβαιώθηκε!</strong> Η πληρωμή σας επεξεργάσθηκε με επιτυχία.
</div>

<div class="info-box">
    <h3>Στοιχεία Πληρωμής</h3>
    <p><strong>Κωδικός Πληρωμής:</strong> #{{ $payment->id }}</p>
    <p><strong>Ποσό:</strong> €{{ number_format($payment->amount, 2) }}</p>
    <p><strong>Μέθοδος Πληρωμής:</strong> {{ ucfirst($payment->payment_method) }}</p>
    <p><strong>Κωδικός Συναλλαγής:</strong> {{ $payment->transaction_id ?? 'ΜΗ ΔΙΑΘΕΣΙΜΟ' }}</p>
    <p><strong>Ημερομηνία Πληρωμής:</strong> {{ $payment->created_at->format('F j, Y \a\t g:i A') }}</p>
    <p><strong>Κατάσταση:</strong> <span style="color: #28a745; font-weight: 600;">Ολοκληρωμένη</span></p>
</div>

<h2>Σύνοψη Πληρωμής</h2>
<table class="details-table">
    <tr>
        <th>Περιγραφή</th>
        <th>Ποσό</th>
    </tr>
    @if($payment->type === 'package')
    <tr>
        <td>
            <strong>{{ $payment->package->name }}</strong>
            <br><small style="color: #888;">{{ $payment->package->description }}</small>
        </td>
        <td>€{{ number_format($payment->amount, 2) }}</td>
    </tr>
    @elseif($payment->type === 'membership')
    <tr>
        <td>
            <strong>Συνδρομή Ιδιοτήτας</strong>
            @if($payment->membership_period)
            <br><small style="color: #888;">Συνδρομή {{ $payment->membership_period }}</small>
            @endif
        </td>
        <td>€{{ number_format($payment->amount, 2) }}</td>
    </tr>
    @elseif($payment->type === 'order')
    <tr>
        <td>
            <strong>Πληρωμή Παραγγελίας</strong>
            <br><small style="color: #888;">Παραγγελία #{{ $payment->order->order_number }}</small>
        </td>
        <td>€{{ number_format($payment->amount, 2) }}</td>
    </tr>
    @elseif($payment->type === 'appointment')
    <tr>
        <td>
            <strong>{{ $payment->appointment->type === 'ems' ? 'Προπόνηση EMS' : 'Προσωπική Προπόνηση' }}</strong>
            <br><small style="color: #888;">{{ $payment->appointment->scheduled_at->format('F j, Y \a\t g:i A') }}</small>
        </td>
        <td>€{{ number_format($payment->amount, 2) }}</td>
    </tr>
    @else
    <tr>
        <td>
            <strong>{{ $payment->description ?? 'Πληρωμή Γυμναστηρίου' }}</strong>
            @if($payment->reference)
            <br><small style="color: #888;">Αναφ.: {{ $payment->reference }}</small>
            @endif
        </td>
        <td>€{{ number_format($payment->amount, 2) }}</td>
    </tr>
    @endif
    
    @if($payment->tax_amount > 0)
    <tr>
        <td>Φόρος ({{ $payment->tax_rate }}%)</td>
        <td>€{{ number_format($payment->tax_amount, 2) }}</td>
    </tr>
    @endif
    
    @if($payment->discount_amount > 0)
    <tr style="color: #28a745;">
        <td>Εφαρμοσμένη Έκπτωση</td>
        <td>-€{{ number_format($payment->discount_amount, 2) }}</td>
    </tr>
    @endif
    
    <tr style="border-top: 2px solid #333; font-weight: bold; font-size: 16px;">
        <td><strong>Σύνολο Πληρωμής</strong></td>
        <td><strong>€{{ number_format($payment->amount, 2) }}</strong></td>
    </tr>
</table>

@if($payment->type === 'package' && $payment->package)
<div class="alert alert-info">
    <h3>Πακέτο Ενεργοποιήθηκε</h3>
    <p>Το πακέτο {{ $payment->package->name }} είναι πλέον ενεργό και έτοιμο για χρήση!</p>
    <ul style="margin: 10px 0;">
        <li><strong>Μαθήματα που Περιλαμβάνονται:</strong> {{ $payment->package->class_credits ?? 'Απεριόριστα' }}</li>
        <li><strong>Ισχύει Έως:</strong> {{ $payment->package->expires_at ? $payment->package->expires_at->format('F j, Y') : 'Χωρίς λήξη' }}</li>
        <li><strong>Τύπος Πακέτου:</strong> {{ $payment->package->type }}</li>
    </ul>
</div>
@endif

@if($payment->type === 'membership')
<div class="alert alert-info">
    <h3>Ιδιότητα Ενημερώθηκε</h3>
    <p>Η ιδιότητά σας στο γυμναστήριο ενημερώθηκε με αυτή την πληρωμή.</p>
    @if($payment->membership_expires_at)
    <p><strong>Η Ιδιότητα Ισχύει Έως:</strong> {{ $payment->membership_expires_at->format('F j, Y') }}</p>
    @endif
</div>
@endif

<h2>Στοιχεία Απόδειξης</h2>
<div class="info-box">
    <h3>Στοιχεία Πελάτη</h3>
    <p><strong>Όνομα:</strong> {{ $user->first_name }} {{ $user->last_name }}</p>
    <p><strong>Email:</strong> {{ $user->email }}</p>
    <p><strong>Κωδικός Μέλους:</strong> {{ $user->member_id ?? $user->id }}</p>
    @if($user->vat_number)
    <p><strong>ΑΦΜ:</strong> {{ $user->vat_number }}</p>
    @endif
</div>

<div style="text-align: center; margin: 30px 0;">
    <a href="{{ config('app.url') }}/payments/{{ $payment->id }}/receipt" class="btn">Λήψη Απόδειξης</a>
    <a href="{{ config('app.url') }}/payments" class="btn btn-secondary">Προβολή Ιστορικού Πληρωμών</a>
</div>

@if($payment->payment_method === 'bank_transfer')
<h2>Τραπεζική Εμβάσματα Επιβεβαιώθηκε</h2>
<p>Σας ευχαριστούμε για την ολοκλήρωση της τραπεζικής εμβάσματος. Η πληρωμή σας ταιριάστηκε και επεξεργάστηκε με επιτυχία.</p>
@elseif($payment->payment_method === 'card')
<h2>Πληρωμή με Κάρτα Επεξεργάστηκε</h2>
<p>Η πληρωμή σας με κάρτα επεξεργάστηκε ασφαλώς. Θα δείτε αυτή τη χρέωση στο αντίγραφό σας εντός 1-2 εργάσιμων ημερών.</p>
@elseif($payment->payment_method === 'cash')
<h2>Πληρωμή με Μετρητά Καταγράφηκε</h2>
<p>Η πληρωμή σας με μετρητά καταγράφηκε και επεξεργάστηκε στη ρεσεψιόν του γυμναστηρίου.</p>
@endif

@if($payment->notes)
<div class="info-box">
    <h3>Σημειώσεις Πληρωμής</h3>
    <p>{{ $payment->notes }}</p>
</div>
@endif

<h2>Τι Ακολουθεί;</h2>
@if($payment->type === 'package')
<ul>
    <li>Ξεκινήστε να κάνετε κρατήσεις μαθημάτων με το νέο σας πακέτο</li>
    <li>Ελέγξτε το dashboard σας για τα διαθέσιμα credits</li>
    <li>Εξερευνήστε το πρόγραμμα μαθημάτων μας</li>
</ul>
@elseif($payment->type === 'membership')
<ul>
    <li>Απολαύστε όλα τα προνόμια ιδιότητας</li>
    <li>Κάντε κρατήσεις απερίοριστων μαθημάτων (εάν ισχύει)</li>
    <li>Χρησιμοποιήστε τις εγκαταστάσεις κατά τις ώρες λειτουργίας</li>
</ul>
@elseif($payment->type === 'order')
<ul>
    <li>Η παραγγελία σας θα επεξεργαστεί άμεσα</li>
    <li>Παρακολουθήστε την κατάσταση στον λογαριασμό σας</li>
    <li>Περιμένετε ειδοποίηση για παραλαβή/παράδοση</li>
</ul>
@else
<ul>
    <li>Αποκτήστε πρόσβαση στις υπηρεσίες που πληρώσατε</li>
    <li>Ελέγξτε τον λογαριασμό σας για ενημερωμένη κατάσταση</li>
    <li>Επικοινωνήστε μαζί μας αν έχετε ερωτήσεις</li>
</ul>
@endif

<h2>Χρειάζεστε Αντίγραφο;</h2>
<p>Μπορείτε πάντα να αποκτήσετε πρόσβαση στις αποδείξεις και το ιστορικό πληρωμών σας μέσω του dashboard του λογαριασμού σας, ή να επικοινωνήσετε μαζί μας για επιπλέον αντίγραφα.</p>

<h2>Ερωτήσεις ή Ανησυχίες;</h2>
<p>Εάν έχετε οποιεσδήποτε ερωτήσεις για αυτή την πληρωμή ή χρειάζεστε βοήθεια:</p>
<ul>
    <li><strong>Email:</strong> <a href="mailto:billing@sweat93.gr">billing@sweat93.gr</a></li>
    <li><strong>Τηλέφωνο:</strong> <a href="tel:+306980912176">698 091 2176</a></li>
    <li><strong>Προσωπικά:</strong> Επισκεφθείτε τη ρεσεψιόν του γυμναστηρίου</li>
</ul>

<div class="info-box">
    <h3>Ασφάλεια Πληρωμών</h3>
    <p>Όλες οι πληρωμές επεξεργάζονται ασφαλώς χρησιμοποιώντας κρυπτογράφηση βιομηχανικών προτύπων. Τα στοιχεία πληρωμής σας δεν αποθηκεύονται ποτέ στους διακομιστές μας και διαχειρίζονται σε συμμόρφωση με τα πρότυπα PCI DSS.</p>
</div>

<p>Σας ευχαριστούμε για την πληρωμή σας και για το ότι είστε ένας αξιόλογος χρήστης της Sweat93!</p>

<p>Με εκτίμηση,<br>
Η Ομάδα της Sweat93</p>
@endsection