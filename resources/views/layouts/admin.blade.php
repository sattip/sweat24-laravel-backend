<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin Panel') - SWEAT24</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex">
        <!-- Sidebar -->
        <nav class="bg-gray-900 text-white w-64 py-6 px-4">
            <div class="mb-8">
                <h2 class="text-2xl font-bold">SWEAT24</h2>
                <p class="text-sm text-gray-400">Admin Panel</p>
            </div>
            
            <ul class="space-y-2">
                <li>
                    <a href="{{ route('admin.dashboard') }}" class="flex items-center space-x-3 py-2 px-4 rounded hover:bg-gray-800 {{ request()->routeIs('admin.dashboard') ? 'bg-gray-800' : '' }}">
                        <i class="fas fa-dashboard"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('admin.users.index') }}" class="flex items-center space-x-3 py-2 px-4 rounded hover:bg-gray-800 {{ request()->routeIs('admin.users.*') ? 'bg-gray-800' : '' }}">
                        <i class="fas fa-user-shield"></i>
                        <span>Admin Users</span>
                    </a>
                </li>
                <li>
                    <a href="{{ url('/api/v1/users') }}" class="flex items-center space-x-3 py-2 px-4 rounded hover:bg-gray-800">
                        <i class="fas fa-users"></i>
                        <span>All Users</span>
                    </a>
                </li>
                <li>
                    <a href="{{ url('/api/v1/classes') }}" class="flex items-center space-x-3 py-2 px-4 rounded hover:bg-gray-800">
                        <i class="fas fa-calendar"></i>
                        <span>Classes</span>
                    </a>
                </li>
                <li>
                    <a href="{{ url('/api/v1/packages') }}" class="flex items-center space-x-3 py-2 px-4 rounded hover:bg-gray-800">
                        <i class="fas fa-box"></i>
                        <span>Packages</span>
                    </a>
                </li>
            </ul>
            
            <div class="mt-auto pt-8">
                <div class="border-t border-gray-800 pt-4">
                    <div class="flex items-center space-x-3 px-4">
                        <i class="fas fa-user-circle text-2xl"></i>
                        <div>
                            <p class="font-medium">{{ auth()->user()->name }}</p>
                            <p class="text-sm text-gray-400">{{ auth()->user()->email }}</p>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('admin.logout') }}" class="mt-4">
                        @csrf
                        <button type="submit" class="w-full text-left py-2 px-4 rounded hover:bg-gray-800 text-red-400">
                            <i class="fas fa-sign-out-alt mr-3"></i>
                            Logout
                        </button>
                    </form>
                </div>
            </div>
        </nav>
        
        <!-- Main Content -->
        <main class="flex-1 p-8">
            @if(session('success'))
                <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                    {{ session('success') }}
                </div>
            @endif
            
            @if(session('error'))
                <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                    {{ session('error') }}
                </div>
            @endif
            
            @yield('content')
        </main>
    </div>
</body>
</html>