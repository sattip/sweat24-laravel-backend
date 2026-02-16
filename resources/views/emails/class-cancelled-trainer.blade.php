@extends('emails.layout')

@section('title', 'Ακύρωση Μαθήματος - Sweat93')

@section('content')
<h1>Ακύρωση Μαθήματος</h1>

<p>Γεια σου {{ $instructor->name }},</p>

<p>Σε ενημερώνουμε ότι το παρακάτω μάθημά σου έχει ακυρωθεί.</p>

<div class="info-box">
    <h3>Λεπτομέρειες Μαθήματος</h3>
    <p><strong>Μάθημα:</strong> {{ $class->name }}</p>
    <p><strong>Ημερομηνία:</strong> {{ $class->date ? $class->date->format('l, j F Y') : 'TBA' }}</p>
    <p><strong>Ώρα:</strong> {{ $class->time ?? 'TBA' }}</p>
    <p><strong>Τοποθεσία:</strong> {{ $class->location ?? 'Δεν έχει οριστεί' }}</p>
    <p><strong>Κρατήσεις που ακυρώθηκαν:</strong> {{ $bookingsCount }}</p>
</div>

<p>Όλοι οι συμμετέχοντες έχουν ειδοποιηθεί αυτόματα για την ακύρωση.</p>

<p>Αν έχεις οποιαδήποτε απορία, μη διστάσεις να επικοινωνήσεις μαζί μας.</p>

<p>Με εκτίμηση,<br>
Η Ομάδα του Sweat93</p>
@endsection
