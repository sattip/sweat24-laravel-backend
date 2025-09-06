<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Sweat93 Notification')</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #f4f4f4;
            color: #333333;
        }
        .email-wrapper {
            width: 100%;
            background-color: #f4f4f4;
            padding: 40px 0;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .email-header {
            background: #ffffff;
            padding: 30px;
            text-align: center;
            border-bottom: 3px solid #dc3545;
        }
        .logo {
            display: inline-block;
            margin-bottom: 10px;
        }
        .logo img {
            max-width: 200px;
            height: auto;
            display: block;
        }
        .tagline {
            color: #666666;
            font-size: 14px;
            font-weight: 500;
        }
        .email-body {
            padding: 40px 30px;
        }
        h1 {
            color: #333333;
            font-size: 24px;
            margin-bottom: 20px;
            font-weight: 600;
        }
        h2 {
            color: #555555;
            font-size: 20px;
            margin-top: 30px;
            margin-bottom: 15px;
            font-weight: 600;
        }
        p {
            color: #666666;
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 15px;
        }
        .btn {
            display: inline-block;
            padding: 14px 30px;
            background: linear-gradient(135deg, #dc3545 0%, #8B0000 100%);
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 600;
            font-size: 16px;
            margin: 20px 0;
            text-align: center;
        }
        .btn:hover {
            opacity: 0.9;
        }
        .btn-secondary {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        .info-box {
            background-color: #f8f9fa;
            border-left: 4px solid #dc3545;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .info-box h3 {
            margin: 0 0 10px 0;
            color: #333333;
            font-size: 16px;
            font-weight: 600;
        }
        .info-box p {
            margin: 5px 0;
            font-size: 14px;
        }
        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .details-table th {
            background-color: #f8f9fa;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #555555;
            border-bottom: 2px solid #e9ecef;
        }
        .details-table td {
            padding: 12px;
            border-bottom: 1px solid #e9ecef;
            color: #666666;
        }
        .alert {
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .alert-success {
            background-color: #d4edda;
            border-left: 4px solid #28a745;
            color: #155724;
        }
        .alert-warning {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            color: #856404;
        }
        .alert-danger {
            background-color: #f8d7da;
            border-left: 4px solid #dc3545;
            color: #721c24;
        }
        .alert-info {
            background-color: #d1ecf1;
            border-left: 4px solid #17a2b8;
            color: #0c5460;
        }
        .email-footer {
            background-color: #f8f9fa;
            padding: 30px;
            text-align: center;
            border-top: 1px solid #e9ecef;
        }
        .footer-links {
            margin-bottom: 20px;
        }
        .footer-links a {
            color: #dc3545;
            text-decoration: none;
            margin: 0 10px;
            font-size: 14px;
            font-weight: 500;
        }
        .footer-links a:hover {
            text-decoration: underline;
        }
        .footer-text {
            color: #999999;
            font-size: 12px;
            margin: 10px 0;
        }
        .social-links {
            margin: 20px 0;
        }
        .social-links a {
            display: inline-block;
            width: 35px;
            height: 35px;
            background-color: #dc3545;
            color: #ffffff;
            text-align: center;
            line-height: 35px;
            border-radius: 50%;
            margin: 0 5px;
            text-decoration: none;
            font-size: 16px;
        }
        .social-links a:hover {
            background-color: #8B0000;
        }
        .divider {
            height: 1px;
            background-color: #e9ecef;
            margin: 30px 0;
        }
        @media only screen and (max-width: 600px) {
            .email-container {
                width: 100% !important;
                border-radius: 0;
            }
            .email-body {
                padding: 30px 20px;
            }
            h1 {
                font-size: 22px;
            }
            .btn {
                display: block;
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-container">
            <div class="email-header">
                <a href="https://sweat93.gr" class="logo">
                    <img src="https://sweat24backend.obs.com.gr/logo-dark.png" alt="Sweat93" style="max-width: 200px; height: auto;">
                </a>
                <div class="tagline">Το Ταξίδι της Φυσικής σας Κατάστασης Αρχίζει Εδώ</div>
            </div>
            
            <div class="email-body">
                @yield('content')
            </div>
            
            <div class="email-footer">
                <div class="footer-links">
                    <a href="https://sweat93.gr/dashboard">Πίνακας Ελέγχου</a>
                    <a href="https://sweat93.gr/bookings">Οι Κρατήσεις μου</a>
                    <a href="https://sweat93.gr/profile">Προφίλ</a>
                    <a href="https://sweat93.gr/support">Υποστήριξη</a>
                </div>
                
                <div class="divider"></div>
                
                <div style="margin: 20px 0;">
                    <h3 style="color: #333; font-size: 16px; margin-bottom: 15px; font-weight: 600;">Στοιχεία Επικοινωνίας</h3>
                    
                    <div style="margin-bottom: 10px;">
                        <strong style="color: #dc3545;">📍 Διεύθυνση</strong><br>
                        <span style="color: #666;">Ηφαίστου 4, Βάρη 16672</span>
                    </div>
                    
                    <div style="margin-bottom: 10px;">
                        <strong style="color: #dc3545;">📞 Τηλέφωνο</strong><br>
                        <a href="tel:+302101234567" style="color: #666; text-decoration: none;">+30 210 123 4567</a>
                    </div>
                    
                    <div style="margin-bottom: 10px;">
                        <strong style="color: #dc3545;">✉️ Email</strong><br>
                        <a href="mailto:info@sweat93.com" style="color: #666; text-decoration: none;">info@sweat93.com</a>
                    </div>
                    
                    <div style="margin-bottom: 10px;">
                        <strong style="color: #dc3545;">🕐 Ωράριο</strong><br>
                        <span style="color: #666; font-size: 14px;">
                            08:00 - 20:00<br>
                            09:00 - 18:00
                        </span>
                    </div>
                </div>
                
                <div class="divider"></div>
                
                <p class="footer-text">
                    © {{ date('Y') }} Sweat93. Όλα τα δικαιώματα διατηρούνται.
                </p>
                
                @if(isset($unsubscribe) && $unsubscribe)
                <p class="footer-text">
                    <a href="{{ $unsubscribe }}" style="color: #999999;">Διαγραφή από αυτά τα emails</a>
                </p>
                @endif
            </div>
        </div>
    </div>
</body>
</html>