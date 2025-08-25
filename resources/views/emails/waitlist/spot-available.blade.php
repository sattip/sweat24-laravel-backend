@extends('emails.layout')

@section('content')
<h1 style="color: #333; font-size: 24px; margin-bottom: 20px;">🎉 Μια Θέση Είναι Διαθέσιμη!</h1>

<p style="color: #666; font-size: 16px; line-height: 1.5; margin-bottom: 20px;">
    Γεια σας {{ $user->first_name ?? $user->name }},
</p>

<p style="color: #666; font-size: 16px; line-height: 1.5; margin-bottom: 20px;">
    Καλά νέα! Μια θέση έχει γίνει διαθέσιμη στην τάξη που είστε στη λίστα αναμονής.
</p>

<div style="background-color: #fff3cd; border-radius: 8px; padding: 20px; margin-bottom: 30px; border-left: 4px solid #ffc107;">
    <h2 style="color: #856404; font-size: 18px; margin-bottom: 15px;">⏰ Ενεργήστε Γρήγορα!</h2>
    <p style="color: #856404; font-size: 16px; margin-bottom: 10px;">
        Αυτή η θέση θα παραμείνει διαθέσιμη μέχρι:
    </p>
    <p style="color: #856404; font-size: 20px; font-weight: bold; margin: 0;">
        {{ \Carbon\Carbon::parse($expiresAt)->format('d/m/Y H:i') }}
    </p>
</div>

<div style="background-color: #f8f9fa; border-radius: 8px; padding: 20px; margin-bottom: 30px;">
    <h2 style="color: #333; font-size: 18px; margin-bottom: 15px;">📅 Λεπτομέρειες Τάξης</h2>
    
    <table style="width: 100%; border-collapse: collapse;">
        <tr>
            <td style="padding: 8px 0; color: #666; width: 40%;">Τάξη:</td>
            <td style="padding: 8px 0; color: #333; font-weight: bold;">{{ $gymClass->name }}</td>
        </tr>
        <tr>
            <td style="padding: 8px 0; color: #666;">Εκπαιδευτής:</td>
            <td style="padding: 8px 0; color: #333; font-weight: bold;">{{ $gymClass->instructor }}</td>
        </tr>
        <tr>
            <td style="padding: 8px 0; color: #666;">Ημερομηνία:</td>
            <td style="padding: 8px 0; color: #333; font-weight: bold;">
                {{ \Carbon\Carbon::parse($gymClass->date)->format('d/m/Y') }}
            </td>
        </tr>
        <tr>
            <td style="padding: 8px 0; color: #666;">Ώρα:</td>
            <td style="padding: 8px 0; color: #333; font-weight: bold;">{{ $gymClass->time }}</td>
        </tr>
        <tr>
            <td style="padding: 8px 0; color: #666;">Διάρκεια:</td>
            <td style="padding: 8px 0; color: #333; font-weight: bold;">{{ $gymClass->duration }} λεπτά</td>
        </tr>
        @if($gymClass->description)
        <tr>
            <td style="padding: 8px 0; color: #666; vertical-align: top;">Περιγραφή:</td>
            <td style="padding: 8px 0; color: #333;">{{ $gymClass->description }}</td>
        </tr>
        @endif
    </table>
</div>

<div style="text-align: center; margin: 40px 0;">
    <a href="{{ config('app.url') }}/book-now?class={{ $gymClass->id }}" 
       style="display: inline-block; padding: 15px 40px; background-color: #28a745; color: white; text-decoration: none; border-radius: 5px; font-weight: bold; font-size: 18px;">
        Κράτηση Τώρα
    </a>
</div>

<div style="background-color: #f8d7da; border-radius: 8px; padding: 20px; margin-top: 30px; border-left: 4px solid #dc3545;">
    <h3 style="color: #721c24; font-size: 16px; margin-bottom: 10px;">⚠️ Προσοχή</h3>
    <p style="color: #721c24; font-size: 14px; margin: 0;">
        Εάν δεν ολοκληρώσετε την κράτηση μέχρι την προθεσμία, η θέση θα δοθεί στον επόμενο στη λίστα αναμονής.
    </p>
</div>

<div style="margin-top: 30px; padding: 15px; background-color: #f8f9fa; border-radius: 5px;">
    <p style="color: #666; font-size: 12px; margin: 0; text-align: center;">
        Εάν δεν ενδιαφέρεστε πλέον για αυτή την τάξη, μπορείτε να αγνοήσετε αυτό το μήνυμα.
    </p>
</div>
@endsection