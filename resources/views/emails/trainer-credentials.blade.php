<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Στοιχεία Σύνδεσης - Sweat 93</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            background-color: #ffffff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            padding-bottom: 20px;
            border-bottom: 3px solid #3B82F6;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #3B82F6;
            margin: 0;
            font-size: 28px;
        }
        .content {
            margin-bottom: 30px;
        }
        .credentials-box {
            background-color: #f8f9fa;
            border-left: 4px solid #3B82F6;
            padding: 20px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .credentials-box h3 {
            margin-top: 0;
            color: #3B82F6;
        }
        .credential-item {
            margin: 15px 0;
            padding: 10px;
            background-color: #ffffff;
            border-radius: 5px;
        }
        .credential-label {
            font-weight: bold;
            color: #666;
            font-size: 12px;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        .credential-value {
            font-size: 18px;
            color: #333;
            font-family: 'Courier New', monospace;
            word-break: break-all;
        }
        .login-url {
            text-align: center;
            margin: 30px 0;
        }
        .login-button {
            display: inline-block;
            background-color: #3B82F6;
            color: #ffffff;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            font-size: 16px;
        }
        .login-button:hover {
            background-color: #2563EB;
        }
        .warning {
            background-color: #FEF3C7;
            border-left: 4px solid #F59E0B;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .warning p {
            margin: 5px 0;
            color: #92400E;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
            color: #666;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🏋️ Sweat 93</h1>
            <p>Καλώς ήρθες στην ομάδα μας!</p>
        </div>

        <div class="content">
            <p>Γεια σου <strong>{{ $trainerName }}</strong>,</p>

            <p>Καλώς ήρθες στο Sweat 93! Έχει δημιουργηθεί ο λογαριασμός σου ως προπονητής στο σύστημά μας.</p>

            <div class="credentials-box">
                <h3>📧 Στοιχεία Σύνδεσης</h3>

                <div class="credential-item">
                    <div class="credential-label">Email / Όνομα Χρήστη</div>
                    <div class="credential-value">{{ $email }}</div>
                </div>

                <div class="credential-item">
                    <div class="credential-label">Προσωρινός Κωδικός</div>
                    <div class="credential-value">{{ $password }}</div>
                </div>
            </div>

            <div class="login-url">
                <a href="https://panel.sweat93.gr" class="login-button">
                    🔐 Σύνδεση στο Panel
                </a>
            </div>

            <div class="warning">
                <p><strong>⚠️ Σημαντικό:</strong></p>
                <p>• Αυτός είναι ένας <strong>προσωρινός κωδικός</strong>. Παρακαλούμε άλλαξέ τον μετά την πρώτη σύνδεση.</p>
                <p>• Μην μοιράζεσαι τα στοιχεία σύνδεσής σου με κανέναν.</p>
                <p>• Αν δεν μπορείς να συνδεθείς, επικοινώνησε με τον διαχειριστή.</p>
            </div>

            <p>Μέσω του panel μπορείς να:</p>
            <ul>
                <li>Διαχειρίζεσαι τα ραντεβού σου</li>
                <li>Βλέπεις το πρόγραμμά σου</li>
                <li>Παρακολουθείς τους πελάτες σου</li>
                <li>Καταγράφεις τις ώρες εργασίας σου</li>
            </ul>

            <p>Αν έχεις οποιαδήποτε ερώτηση, μη διστάσεις να επικοινωνήσεις μαζί μας!</p>

            <p>Καλή αρχή! 💪</p>
        </div>

        <div class="footer">
            <p><strong>Sweat 93 Gym</strong></p>
            <p>Αυτό το email στάλθηκε αυτόματα. Παρακαλούμε μην απαντήσεις.</p>
        </div>
    </div>
</body>
</html>
