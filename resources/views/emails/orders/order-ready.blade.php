@extends('emails.layout')

@section('title', 'Παραγγελία Έτοιμη - Sweat93')

@section('content')
<h1>Η Παραγγελία σας είναι Έτοιμη!</h1>

<p>Υπέροχα νέα, {{ $user->first_name }}! Η παραγγελία σας είναι πλέον έτοιμη για {{ $order->delivery_type === 'pickup' ? 'παραλαβή' : 'παράδοση' }}.</p>

@if($order->delivery_type === 'pickup')
<div class="alert alert-success">
    <strong>Έτοιμη για Παραλαβή!</strong> Η παραγγελία σας σας περιμένει στη ρεσεψιόν του γυμναστηρίου.
</div>
@else
<div class="alert alert-success">
    <strong>Σε Μεταφορά για Παράδοση!</strong> Η παραγγελία σας είναι καθ' οδόν προς τη διεύθυνση παράδοσής σας.
</div>
@endif

<div class="info-box">
    <h3>Στοιχεία Παραγγελίας</h3>
    <p><strong>Αριθμός Παραγγελίας:</strong> #{{ $order->order_number }}</p>
    <p><strong>Πελάτης:</strong> {{ $user->first_name }} {{ $user->last_name }}</p>
    <p><strong>Συνολικό Ποσό:</strong> €{{ number_format($order->total, 2) }}</p>
    <p><strong>Κατάσταση:</strong> <span style="color: #28a745; font-weight: 600;">Έτοιμη</span></p>
    <p><strong>Ετοιμάστηκε Στις:</strong> {{ now()->format('F j, Y \a\t g:i A') }}</p>
</div>

@if($order->delivery_type === 'pickup')
<h2>Πληροφορίες Παραλαβής</h2>
<div class="alert alert-info">
    <h3>Στοιχεία Παραλαβής</h3>
    <p><strong>Τοποθεσία:</strong> Sweat93 Ρεσεψιόν Γυμναστηρίου</p>
    <p><strong>Διεύθυνση:</strong> Your Gym Address Here</p>
    <p><strong>Ώρες Λειτουργίας:</strong> Δευτέρα - Παρασκευή: 6:00 π.μ. - 10:00 μ.μ. | Σαββατοκύριακα: 8:00 π.μ. - 8:00 μ.μ.</p>
    <p><strong>Διατηρείται Έως:</strong> {{ now()->addDays(3)->format('F j, Y') }} (3 ημέρες)</p>
</div>

<h2>Τι να Φέρετε</h2>
<ul>
    <li>Email επιβεβαίωσης παραγγελίας (αυτό το email) ή αριθμό παραγγελίας</li>
    <li>Έγκυρη ταυτότητα με φωτογραφία</li>
    <li>Μέθοδο πληρωμής (εάν η πληρωμή εκκρεμεί)</li>
</ul>

@else

<h2>Πληροφορίες Παράδοσης</h2>
<div class="alert alert-info">
    <h3>Στοιχεία Παράδοσης</h3>
    <p><strong>Διεύθυνση Παράδοσης:</strong><br>{{ $order->delivery_address }}</p>
    <p><strong>Εκτιμώμενη Άφιξη:</strong> {{ $order->estimated_delivery_time ? $order->estimated_delivery_time->format('g:i A \o\n F j, Y') : 'Εντός 1 ώρας' }}</p>
    <p><strong>Τηλέφωνο Επικοινωνίας:</strong> {{ $order->delivery_phone ?? $user->phone ?? 'Επικοινωνήστε με το γυμναστήριο για ενημερώσεις' }}</p>
</div>

<h2>Οδηγίες Παράδοσης</h2>
<ul>
    <li>Παρακαλώ να είστε διαθέσιμοι στη διεύθυνση παράδοσης</li>
    <li>Έχετε έτοιμο τον αριθμό παραγγελίας σας: #{{ $order->order_number }}</li>
    <li>Ελέγξτε το τηλέφωνό σας για ενημερώσεις παράδοσης</li>
    @if($order->payment_status === 'pending')
    <li>Η πληρωμή θα εισπραχθεί κατά την παράδοση</li>
    @endif
</ul>

@endif

<h2>Προϊόντα Παραγγελίας</h2>
<table class="details-table">
    <tr>
        <th>Προϊόν</th>
        <th>Ποσότητα</th>
        <th>Κατάσταση</th>
    </tr>
    @foreach($order->items as $item)
    <tr>
        <td>
            <strong>{{ $item->product->name }}</strong>
            @if($item->notes)
            <br><small style="color: #666;"><em>{{ $item->notes }}</em></small>
            @endif
        </td>
        <td>{{ $item->quantity }}</td>
        <td><span style="color: #28a745; font-weight: 600;">Έτοιμο</span></td>
    </tr>
    @endforeach
</table>

@if($order->type === 'meal')
<div class="alert alert-warning">
    <h3>Χειρισμός Φρέσκων Γευμάτων</h3>
    <p><strong>Σημαντικό:</strong> Τα φρέσκα γεύματά σας θα πρέπει να καταναλωθούν ή να τοποθετηθούν σε ψυγείο εντός 2 ωρών από τη {{ $order->delivery_type === 'pickup' ? 'παραλαβή' : 'παράδοση' }} για τη διατήρηση της ποιότητας και ασφάλειας.</p>
    <ul style="margin: 10px 0;">
        <li>Διατηρήστε σε ψυγείο (0-4°C) εάν δεν τα καταναλώνετε άμεσα</li>
        <li>Καταναλώστε εντός 48 ωρών για βέλτιστη ποιότητα</li>
        <li>Ζεσταίνετε καλά πριν την κατανάλωση</li>
        <li>Μη αφήνετε σε θερμοκρασία δωματίου για περισσότερη ώρα</li>
    </ul>
</div>
@endif

@if($order->special_instructions)
<div class="info-box">
    <h3>Ειδικές Οδηγίες</h3>
    <p>{{ $order->special_instructions }}</p>
</div>
@endif

<div style="text-align: center; margin: 30px 0;">
    @if($order->delivery_type === 'pickup')
    <a href="https://sweat93.gr/orders/{{ $order->id }}" class="btn">Προβολή Στοιχείων Παραγγελίας</a>
    <a href="tel:+302101234567" class="btn btn-secondary">Κλήση για Βοήθεια</a>
    @else
    <a href="https://sweat93.gr/orders/{{ $order->id }}/track" class="btn">Παρακολούθηση Παράδοσης</a>
    <a href="https://sweat93.gr/orders/{{ $order->id }}" class="btn btn-secondary">Στοιχεία Παραγγελίας</a>
    @endif
</div>

<h2>Εγγύηση Ποιότητας</h2>
<p>Είμαστε υπερήφανοι για την ποιότητα των προϊόντων μας. Εάν δεν είστε πλήρως ικανοποιημένοι με την παραγγελία σας, παρακαλώ επικοινωνήστε μαζί μας εντός 24 ωρών:</p>

<ul>
    <li><strong>Ελλείποντα Προϊόντα:</strong> Θα παρέχουμε αντικατάσταση άμεσα</li>
    <li><strong>Προβλήματα Ποιότητας:</strong> Πλήρης επιστροφή χρημάτων ή αντικατάσταση διαθέσιμη</li>
    <li><strong>Φθαρμένα Προϊόντα:</strong> Επικοινωνήστε μαζί μας για άμεση επίλυση</li>
</ul>

@if($order->delivery_type === 'pickup')
<h2>Υπενθυμίσεις Παραλαβής</h2>
<ul>
    <li>Παραγγελίες που δεν παραλαμβάνονται εντός 3 ημερών ενδέχεται να απορριφθούν</li>
    <li>Επικοινωνήστε μαζί μας εάν θα καθυστερήσετε στην παραλαβή</li>
    <li>Κάποιος άλλος μπορεί να παραλάβει την παραγγελία σας με αυτή την επιβεβαίωση και ταυτότητα</li>
</ul>
@else
<h2>Σημειώσεις Παράδοσης</h2>
<ul>
    <li>Ο συνεργάτης παράδοσής μας θα επικοινωνήσει μαζί σας πριν την άφιξη</li>
    <li>Εάν δεν είστε διαθέσιμοι, θα αφήσει σημείωμα παράδοσης</li>
    <li>Επικοινωνήστε μαζί μας άμεσα εάν υπάρχουν προβλήματα παράδοσης</li>
</ul>
@endif

<h2>Χρειάζεστε Βοήθεια;</h2>
<p>Για οποιεσδήποτε ερωτήσεις ή προβλήματα με την παραγγελία σας:</p>
<ul>
    <li><strong>Email:</strong> <a href="mailto:info@sweat93.com">info@sweat93.com</a></li>
    <li><strong>Τηλέφωνο:</strong> <a href="tel:+302101234567">+30 210 123 4567</a></li>
    <li><strong>WhatsApp:</strong> Γρήγορες ενημερώσεις και υποστήριξη</li>
    <li><strong>Προσωπικά:</strong> Επισκεφθείτε τη ρεσεψιόν του γυμναστηρίου</li>
</ul>

<p>Σας ευχαριστούμε που επιλέξατε τη Sweat93. Να ευχαριστηθείτε την παραγγελία σας!</p>

<p>Με εκτίμηση,<br>
Η Ομάδα της Sweat93</p>
@endsection