@extends('emails.layout')

@section('title', 'Επιβεβαίωση Παραγγελίας - Sweat93')

@section('content')
<h1>Η Παραγγελία Επιβεβαιώθηκε!</h1>

<p>Σας ευχαριστούμε, {{ $user->first_name }}! Η παραγγελία σας έχει καταχωρηθεί επιτυχώς και επιβεβαιώθηκε.</p>

<div class="alert alert-success">
    <strong>Παραγγελία Επιβεβαιώθηκε!</strong> Προετοιμάζουμε τα είδη σας και θα σας ειδοποιήσουμε όταν είναι έτοιμα.
</div>

<div class="info-box">
    <h3>Περίληψη Παραγγελίας</h3>
    <p><strong>Αριθμός Παραγγελίας:</strong> #{{ $order->order_number }}</p>
    <p><strong>Ημερομηνία Παραγγελίας:</strong> {{ $order->created_at->format('j F Y \s\τ\ι\ς g:i A') }}</p>
    <p><strong>Πελάτης:</strong> {{ $user->first_name }} {{ $user->last_name }}</p>
    <p><strong>Email:</strong> {{ $user->email }}</p>
    <p><strong>Τηλέφωνο:</strong> {{ $user->phone ?? 'Δεν δόθηκε' }}</p>
</div>

<h2>Λεπτομέρειες Παραγγελίας</h2>
<table class="details-table">
    <tr>
        <th>Είδος</th>
        <th>Ποσότητα</th>
        <th>Τιμή</th>
        <th>Σύνολο</th>
    </tr>
    @foreach($order->items as $item)
    <tr>
        <td>
            <strong>{{ $item->product->name }}</strong>
            @if($item->product->description)
            <br><small style="color: #888;">{{ Str::limit($item->product->description, 50) }}</small>
            @endif
            @if($item->notes)
            <br><small style="color: #666;"><em>Note: {{ $item->notes }}</em></small>
            @endif
        </td>
        <td>{{ $item->quantity }}</td>
        <td>€{{ number_format($item->price, 2) }}</td>
        <td><strong>€{{ number_format($item->total, 2) }}</strong></td>
    </tr>
    @endforeach
</table>

<table class="details-table" style="margin-top: 20px;">
    <tr>
        <td colspan="3" style="text-align: right; font-weight: 600;">Υποσύνολο:</td>
        <td><strong>€{{ number_format($order->subtotal, 2) }}</strong></td>
    </tr>
    @if($order->tax_amount > 0)
    <tr>
        <td colspan="3" style="text-align: right;">Φόρος ({{ $order->tax_rate }}%):</td>
        <td>€{{ number_format($order->tax_amount, 2) }}</td>
    </tr>
    @endif
    @if($order->discount_amount > 0)
    <tr style="color: #28a745;">
        <td colspan="3" style="text-align: right;">Έκπτωση:</td>
        <td>-€{{ number_format($order->discount_amount, 2) }}</td>
    </tr>
    @endif
    <tr style="border-top: 2px solid #333; font-size: 18px; font-weight: bold;">
        <td colspan="3" style="text-align: right;">Σύνολο:</td>
        <td><strong>€{{ number_format($order->total, 2) }}</strong></td>
    </tr>
</table>

@if($order->delivery_type === 'pickup')
<div class="alert alert-info">
    <h3>Πληροφορίες Παραλαβής</h3>
    <p><strong>Σημείο Παραλαβής:</strong> Υποδοχή Sweat93 Gym</p>
    <p><strong>Ώρες Διαθεσιμότητας:</strong> Δευτέρα - Παρασκευή: 6:00 πμ - 10:00 μμ | Σαββατοκύριακα: 8:00 πμ - 8:00 μμ</p>
    <p><strong>Εκτιμώμενη Ώρα Ετοιμότητας:</strong> {{ $order->estimated_ready_time ? $order->estimated_ready_time->format('j F Y \s\τ\ι\ς g:i A') : 'Εντός 2 ωρών' }}</p>
    <p>Θα σας στείλουμε ειδοποίηση όταν η παραγγελία σας είναι έτοιμη για παραλαβή.</p>
</div>
@elseif($order->delivery_type === 'delivery')
<div class="alert alert-info">
    <h3>Πληροφορίες Παράδοσης</h3>
    <p><strong>Διεύθυνση Παράδοσης:</strong><br>
    {{ $order->delivery_address }}</p>
    <p><strong>Εκτιμώμενη Παράδοση:</strong> {{ $order->estimated_delivery_time ? $order->estimated_delivery_time->format('j F Y \s\τ\ι\ς g:i A') : 'Εντός 24 ωρών' }}</p>
    <p><strong>Κόστος Παράδοσης:</strong> {{ $order->delivery_fee ? '€' . number_format($order->delivery_fee, 2) : 'Δωρεάν' }}</p>
</div>
@endif

@if($order->payment_status === 'paid')
<div class="alert alert-success">
    <h3>Πληρωμή Επιβεβαιώθηκε</h3>
    <p><strong>Τρόπος Πληρωμής:</strong> {{ ucfirst($order->payment_method) }}</p>
    <p><strong>ID Συναλλαγής:</strong> {{ $order->transaction_id ?? 'N/A' }}</p>
    <p><strong>Ποσό Που Πληρώθηκε:</strong> €{{ number_format($order->total, 2) }}</p>
</div>
@elseif($order->payment_status === 'pending')
<div class="alert alert-warning">
    <h3>Πληρωμή Εκκρεμεί</h3>
    <p>Η πληρωμή σας επεξεργάζεται. Θα σας ενημερώσουμε μόλις η πληρωμή επιβεβαιωθεί.</p>
    @if($order->payment_method === 'bank_transfer')
    <p><strong>Στοιχεία Τραπεζικής Εμβάσματος:</strong> Ελέγξτε το email σας για οδηγίες πληρωμής.</p>
    @endif
</div>
@endif

<h2>Τι Ακολουθεί;</h2>
@if($order->type === 'meal')
<ol>
    <li><strong>Προετοιμασία Γευμάτων:</strong> Η ομάδα κουζίνας μας θα προετοιμάσει τα φρέσκα γεύματά σας</li>
    <li><strong>Έλεγχος Ποιότητας:</strong> Κάθε γεύμα συσκευάζεται με προσοχή και ελέγχεται</li>
    <li><strong>Ειδοποίηση Ετοιμότητας:</strong> Θα λάβετε email/SMS όταν είναι έτοιμα</li>
    <li><strong>{{ ucfirst($order->delivery_type === 'pickup' ? 'Παραλαβή' : 'Παράδοση') }}:</strong> {{ $order->delivery_type === 'pickup' ? 'Έρθετε στο γυμναστήριο να παραλάβετε τα γεύματά σας' : 'Τα γεύματά σας θα παραδοθούν στη διεύθυνσή σας' }}</li>
</ol>
@else
<ol>
    <li><strong>Επεξεργασία Παραγγελίας:</strong> Ισσυγκεντρώνουμε τα είδη σας</li>
    <li><strong>Έλεγχος Ποιότητας:</strong> Κάθε προϊόν ελέγχεται με προσοχή και συσκευάζεται</li>
    <li><strong>Ειδοποίηση Ετοιμότητας:</strong> Θα λάβετε επιβεβαίωση όταν είναι έτοιμα</li>
    <li><strong>{{ ucfirst($order->delivery_type === 'pickup' ? 'Παραλαβή' : 'Παράδοση') }}:</strong> {{ $order->delivery_type === 'pickup' ? 'Επισκεφθείτε την υποδοχή του γυμναστηρίου για παραλαβή' : 'Η παραγγελία σας θα παραδοθεί' }}</li>
</ol>
@endif

<div style="text-align: center; margin: 30px 0;">
    <a href="https://sweat93.gr/orders/{{ $order->id }}" class="btn">Παρακολούθηση Παραγγελίας</a>
    <a href="https://sweat93.gr/orders" class="btn btn-secondary">Προβολή Όλων των Παραγγελιών</a>
</div>

@if($order->special_instructions)
<div class="info-box">
    <h3>Ειδικές Οδηγίες</h3>
    <p>{{ $order->special_instructions }}</p>
</div>
@endif

<h2>Χρειάζεστε Βοήθεια;</h2>
<p>Αν έχετε ερωτήσεις σχετικά με την παραγγελία σας ή χρειάζεστε να κάνετε αλλαγές:</p>
<ul>
    <li><strong>Email:</strong> <a href="mailto:info@sweat93.com">info@sweat93.com</a></li>
    <li><strong>Τηλέφωνο:</strong> <a href="tel:+302101234567">+30 210 123 4567</a></li>
    <li><strong>WhatsApp:</strong> Διαθέσιμο για ενημερώσεις παραγγελιών</li>
</ul>

<div class="info-box">
    <h3>Πολιτική Παραγγελίας</h3>
    <p><strong>Ακυρώσεις:</strong> Οι παραγγελίες μπορούν να ακυρωθούν εντός 15 λεπτών από την καταχώρηση</p>
    <p><strong>Αλλαγές:</strong> Επικοινωνήστε μαζί μας άμεσα Εάν χρειάζεται να τροποποιήσετε την παραγγελία σας</p>
    <p><strong>Επιστροφές:</strong> Επικοινωνήστε μαζί μας εντός 24 ωρών για οποιοδήποτε ζήτημα</p>
</div>

<p>Σας ευχαριστούμε που επιλέξατε το Sweat93 για τις διατροφικές σας ανάγκες!</p>

<p>Με εκτίμηση,<br>
Η Ομάδα του Sweat93</p>
@endsection