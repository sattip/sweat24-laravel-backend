<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SWEAT24 - Login</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
        }
        .login-container {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
        }
        .logo {
            text-align: center;
            margin-bottom: 2rem;
        }
        .logo h1 {
            color: #333;
            margin: 0;
            font-size: 2rem;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        label {
            display: block;
            margin-bottom: 0.5rem;
            color: #555;
            font-weight: bold;
        }
        input[type="email"], input[type="password"] {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            box-sizing: border-box;
        }
        input[type="email"]:focus, input[type="password"]:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.2);
        }
        .btn {
            width: 100%;
            padding: 0.75rem;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
            transition: background 0.3s;
        }
        .btn:hover {
            background: #5a6fd8;
        }
        .error {
            color: #e74c3c;
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }
        .demo-credentials {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 5px;
            margin-top: 1rem;
            font-size: 0.875rem;
        }
        .demo-credentials strong {
            color: #333;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <h1>SWEAT24</h1>
            <p>Gym Management System</p>
        </div>
        
        <form method="POST" action="{{ route('login.submit') }}">
            @csrf
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus>
                @error('email')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
                @error('password')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>
            
            <button type="submit" class="btn">Login</button>
        </form>
        
        <div class="demo-credentials">
            <strong>Demo Accounts:</strong><br>
            <strong>Admin:</strong> admin@sweat24.gr / password<br>
            <strong>Member:</strong> user@sweat24.gr / password
        </div>
    </div>
</body>
</html>