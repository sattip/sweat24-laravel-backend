<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ερωτηματολόγιο - Sweat93</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            min-height: 100vh;
            padding: 20px;
            color: #333;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
        }
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo h1 {
            color: #c41e3a;
            font-size: 28px;
            font-weight: 700;
        }
        .greeting {
            text-align: center;
            margin-bottom: 30px;
        }
        .greeting h2 {
            color: #333;
            font-size: 22px;
            margin-bottom: 10px;
        }
        .greeting p {
            color: #666;
            font-size: 15px;
            line-height: 1.6;
        }
        .form-group {
            margin-bottom: 25px;
        }
        .form-group label {
            display: block;
            font-weight: 600;
            color: #333;
            margin-bottom: 12px;
            font-size: 15px;
        }
        .radio-group, .checkbox-group {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .radio-option, .checkbox-option {
            display: flex;
            align-items: center;
            padding: 12px 16px;
            background: #f8f9fa;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.2s;
            border: 2px solid transparent;
        }
        .radio-option:hover, .checkbox-option:hover {
            background: #f0f0f0;
        }
        .radio-option.selected, .checkbox-option.selected {
            background: #fff0f3;
            border-color: #c41e3a;
        }
        .radio-option input, .checkbox-option input {
            margin-right: 12px;
            accent-color: #c41e3a;
            width: 18px;
            height: 18px;
        }
        textarea {
            width: 100%;
            padding: 14px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 15px;
            font-family: inherit;
            resize: vertical;
            min-height: 100px;
            transition: border-color 0.2s;
        }
        textarea:focus {
            outline: none;
            border-color: #c41e3a;
        }
        .slider-container {
            padding: 10px 0;
        }
        .slider-labels {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            color: #666;
            margin-bottom: 8px;
        }
        input[type="range"] {
            width: 100%;
            height: 8px;
            border-radius: 4px;
            background: #e0e0e0;
            outline: none;
            -webkit-appearance: none;
        }
        input[type="range"]::-webkit-slider-thumb {
            -webkit-appearance: none;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: #c41e3a;
            cursor: pointer;
            box-shadow: 0 2px 6px rgba(196, 30, 58, 0.3);
        }
        .slider-value {
            text-align: center;
            font-size: 24px;
            font-weight: 700;
            color: #c41e3a;
            margin-top: 10px;
        }
        .submit-btn {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #c41e3a 0%, #a01830 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(196, 30, 58, 0.4);
        }
        .opt-out-link {
            text-align: center;
            margin-top: 20px;
        }
        .opt-out-link a {
            color: #999;
            font-size: 13px;
            text-decoration: none;
        }
        .opt-out-link a:hover {
            text-decoration: underline;
        }
        .section-divider {
            border: none;
            border-top: 1px solid #e0e0e0;
            margin: 30px 0;
        }
        .info-box {
            background: #e8f4fd;
            border-left: 4px solid #2196F3;
            padding: 15px;
            border-radius: 0 8px 8px 0;
            margin-bottom: 20px;
        }
        .info-box p {
            color: #1565C0;
            font-size: 14px;
            margin: 0;
        }
        @media (max-width: 480px) {
            .container {
                padding: 20px;
            }
            .greeting h2 {
                font-size: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <h1>SWEAT93</h1>
        </div>

        <div class="greeting">
            <h2>Γεια σου {{ $user->name ?? 'φίλε' }}!</h2>
            <p>Θα θέλαμε να μάθουμε τη γνώμη σου. Οι απαντήσεις σου θα μας βοηθήσουν να βελτιωθούμε.</p>
        </div>

        <form action="{{ route('churn-survey.submit', $feedback->id) }}" method="POST">
            @csrf

            <div class="form-group">
                <label>Ποιος είναι ο κύριος λόγος που σκέφτεσαι να σταματήσεις;</label>
                <div class="radio-group">
                    @foreach($reasonOptions as $key => $label)
                        <label class="radio-option" onclick="this.classList.toggle('selected', this.querySelector('input').checked)">
                            <input type="radio" name="reason" value="{{ $key }}" required onchange="updateSelection(this)">
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="form-group">
                <label>Υπάρχουν κι άλλοι λόγοι; (προαιρετικό)</label>
                <div class="checkbox-group">
                    @foreach($reasonOptions as $key => $label)
                        <label class="checkbox-option" onclick="this.classList.toggle('selected', this.querySelector('input').checked)">
                            <input type="checkbox" name="other_reasons[]" value="{{ $key }}" onchange="this.parentElement.classList.toggle('selected', this.checked)">
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="form-group">
                <label>Θέλεις να μας πεις κάτι παραπάνω; (προαιρετικό)</label>
                <textarea name="comment" placeholder="Γράψε εδώ τα σχόλιά σου..."></textarea>
            </div>

            <hr class="section-divider">

            <div class="form-group">
                <label>Τι θα μπορούσαμε να κάνουμε καλύτερα; (προαιρετικό)</label>
                <div class="checkbox-group">
                    @foreach($improvementOptions as $key => $label)
                        <label class="checkbox-option" onclick="this.classList.toggle('selected', this.querySelector('input').checked)">
                            <input type="checkbox" name="improvements[]" value="{{ $key }}" onchange="this.parentElement.classList.toggle('selected', this.checked)">
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="form-group">
                <label>Πόσο πιθανό είναι να επιστρέψεις στο μέλλον;</label>
                <div class="slider-container">
                    <div class="slider-labels">
                        <span>Καθόλου πιθανό</span>
                        <span>Πολύ πιθανό</span>
                    </div>
                    <input type="range" name="return_intent_score" min="0" max="10" value="5" oninput="document.getElementById('sliderValue').textContent = this.value">
                    <div class="slider-value" id="sliderValue">5</div>
                </div>
            </div>

            <div class="form-group">
                <label>Σκέφτεσαι να επιστρέψεις κάποια στιγμή;</label>
                <div class="radio-group">
                    <label class="radio-option" onclick="this.classList.toggle('selected', this.querySelector('input').checked)">
                        <input type="radio" name="future_return_intent" value="yes" onchange="updateSelection(this)">
                        Ναι, σίγουρα
                    </label>
                    <label class="radio-option" onclick="this.classList.toggle('selected', this.querySelector('input').checked)">
                        <input type="radio" name="future_return_intent" value="maybe" onchange="updateSelection(this)">
                        Ίσως
                    </label>
                    <label class="radio-option" onclick="this.classList.toggle('selected', this.querySelector('input').checked)">
                        <input type="radio" name="future_return_intent" value="no" onchange="updateSelection(this)">
                        Όχι
                    </label>
                </div>
            </div>

            <div class="info-box">
                <p>Θα ήθελες να σου προτείνουμε ένα εναλλακτικό πακέτο που μπορεί να σου ταιριάζει καλύτερα;</p>
            </div>

            <div class="form-group">
                <div class="radio-group">
                    <label class="radio-option" onclick="this.classList.toggle('selected', this.querySelector('input').checked)">
                        <input type="radio" name="wants_alternative_package" value="1" onchange="updateSelection(this)">
                        Ναι, θέλω να δω εναλλακτικές
                    </label>
                    <label class="radio-option" onclick="this.classList.toggle('selected', this.querySelector('input').checked)">
                        <input type="radio" name="wants_alternative_package" value="0" onchange="updateSelection(this)">
                        Όχι, ευχαριστώ
                    </label>
                </div>
            </div>

            <button type="submit" class="submit-btn">Υποβολή Απαντήσεων</button>
        </form>

        <div class="opt-out-link">
            <a href="{{ route('churn-survey.opt-out', $feedback->id) }}">Δεν θέλω να λαμβάνω τέτοια μηνύματα</a>
        </div>
    </div>

    <script>
        function updateSelection(input) {
            // Remove selected class from all options in the same group
            const name = input.name;
            document.querySelectorAll(`input[name="${name}"]`).forEach(i => {
                i.closest('.radio-option').classList.remove('selected');
            });
            // Add selected class to the checked option
            if (input.checked) {
                input.closest('.radio-option').classList.add('selected');
            }
        }
    </script>
</body>
</html>
