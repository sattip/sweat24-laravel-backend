<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SWEAT24 - Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            background: #f5f5f5;
        }
        .header {
            background: #667eea;
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header h1 {
            margin: 0;
        }
        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        .card {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        .btn {
            padding: 0.5rem 1rem;
            background: #dc3545;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            border: none;
            cursor: pointer;
        }
        .btn:hover {
            background: #c82333;
        }
        .btn-primary {
            background: #007bff;
        }
        .btn-primary:hover {
            background: #0056b3;
        }
        .links {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-top: 2rem;
        }
        .link-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.2s;
        }
        .link-card:hover {
            transform: translateY(-2px);
        }
        .link-card a {
            text-decoration: none;
            color: #333;
            font-weight: bold;
        }
        .link-card a:hover {
            color: #667eea;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>SWEAT24 Dashboard</h1>
        <div class="user-info">
            <span>Welcome, {{ $user->name }}</span>
            <form method="POST" action="{{ route('logout') }}" style="display: inline;">
                @csrf
                <button type="submit" class="btn">Logout</button>
            </form>
        </div>
    </div>
    
    <div class="container">
        <div class="card">
            <h2>User Information</h2>
            <p><strong>Name:</strong> {{ $user->name }}</p>
            <p><strong>Email:</strong> {{ $user->email }}</p>
            <p><strong>Role:</strong> {{ ucfirst($user->role) }}</p>
            <p><strong>Membership:</strong> {{ $user->membership_type }}</p>
            @if($user->role === 'member')
                <p><strong>Remaining Sessions:</strong> {{ $user->remaining_sessions }}</p>
            @endif
        </div>
        
        <div class="card">
            <h2>Quick Links</h2>
            <div class="links">
                @if($user->role === 'admin')
                    <div class="link-card">
                        <a href="http://127.0.0.1:5174" target="_blank">
                            <h3>Admin Panel</h3>
                            <p>Manage users, classes, and bookings</p>
                        </a>
                    </div>
                @endif
                
                <div class="link-card">
                    <a href="http://127.0.0.1:5173" target="_blank">
                        <h3>Client App</h3>
                        <p>Book classes and manage your schedule</p>
                    </a>
                </div>
                
                <div class="link-card">
                    <a href="/api/v1/auth/me" target="_blank">
                        <h3>API Profile</h3>
                        <p>View your profile data via API</p>
                    </a>
                </div>
                
                <div class="link-card">
                    <a href="/api/v1/bookings" target="_blank">
                        <h3>My Bookings API</h3>
                        <p>View your bookings via API</p>
                    </a>
                </div>
                
                @if($user->role === 'admin')
                    <div class="link-card">
                        <a href="/admin/dashboard" target="_blank">
                            <h3>Admin Dashboard</h3>
                            <p>Traditional admin interface</p>
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</body>
</html>