@extends('emails.layout')

@section('title', 'Θα θέλαμε τη γνώμη σου - Sweat93')

@section('content')
    <h1>Γεια σου {{ $user->name }}!</h1>

    <p>
        Μάθαμε ότι σκέφτεσαι να διακόψεις τη συνδρομή σου στο Sweat93 και θα θέλαμε να μάθουμε τη γνώμη σου.
    </p>

    <div class="info-box">
        <h3>Η άποψή σου μετράει!</h3>
        <p>
            Αφιερώνοντας μόνο 1-2 λεπτά για να απαντήσεις σε μερικές ερωτήσεις, μας βοηθάς να βελτιώσουμε
            τις υπηρεσίες μας και να προσφέρουμε καλύτερη εμπειρία σε όλα τα μέλη μας.
        </p>
    </div>

    <table width="100%" cellpadding="0" cellspacing="0" style="margin: 30px 0;">
        <tr>
            <td align="center">
                <a href="{{ $webSurveyUrl }}"
                   style="display: inline-block; background-color: #c41e3a; color: #ffffff; padding: 16px 40px; text-decoration: none; border-radius: 8px; font-weight: bold; font-size: 16px; mso-padding-alt: 0;">
                    <!--[if mso]>
                    <i style="letter-spacing: 40px; mso-font-width: -100%; mso-text-raise: 30pt;">&nbsp;</i>
                    <![endif]-->
                    <span style="mso-text-raise: 15pt;">Συμπλήρωσε το Ερωτηματολόγιο</span>
                    <!--[if mso]>
                    <i style="letter-spacing: 40px; mso-font-width: -100%;">&nbsp;</i>
                    <![endif]-->
                </a>
            </td>
        </tr>
    </table>

    <p style="text-align: center; font-size: 12px; color: #666;">
        Ή αντέγραψε αυτό το link: <a href="{{ $webSurveyUrl }}" style="color: #c41e3a;">{{ $webSurveyUrl }}</a>
    </p>

    <h2>Εναλλακτικά:</h2>

    <div class="alert alert-success">
        <strong>Μέσω της εφαρμογής Sweat93:</strong><br>
        Άνοιξε την εφαρμογή Sweat93 στο κινητό σου - θα δεις αυτόματα το ερωτηματολόγιο.
    </div>

    <div class="alert alert-info">
        <strong>Τηλεφωνικά:</strong><br>
        Αν προτιμάς, μπορείς να μας καλέσεις στο <a href="tel:+306980912176" style="color: #0c5460; font-weight: bold;">698 091 2176</a>
        και θα καταγράψουμε εμείς τις απαντήσεις σου.
    </div>

    <div class="divider"></div>

    <h2>Τι θα μας πεις;</h2>
    <p>
        Θέλουμε να καταλάβουμε τους λόγους που σκέφτεσαι να αποχωρήσεις, ώστε:
    </p>
    <ul style="color: #666666; line-height: 1.8;">
        <li>Να δούμε αν μπορούμε να σε βοηθήσουμε με κάποια εναλλακτική λύση</li>
        <li>Να βελτιώσουμε τις υπηρεσίες μας για όλους</li>
        <li>Να σε καλωσορίσουμε ξανά στο μέλλον, αν το επιθυμείς</li>
    </ul>

    <p>
        Ευχαριστούμε που ήσουν μέλος της οικογένειας Sweat93!
    </p>

    <p>
        Με εκτίμηση,<br>
        <strong>Η ομάδα του Sweat93</strong>
    </p>

    <p style="font-size: 12px; color: #999; margin-top: 30px; text-align: center;">
        <a href="{{ $webSurveyUrl }}/opt-out" style="color: #999;">Δεν θέλω να λαμβάνω τέτοια μηνύματα</a>
    </p>
@endsection
