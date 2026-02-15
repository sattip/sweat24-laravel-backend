@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
<div class="max-w-7xl mx-auto">
    <h1 class="text-3xl font-bold text-gray-800 mb-8">Dashboard</h1>
    
    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Total Users</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $stats['total_users'] }}</p>
                </div>
                <i class="fas fa-users text-3xl text-blue-500"></i>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Admin Users</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $stats['admin_users'] }}</p>
                </div>
                <i class="fas fa-user-shield text-3xl text-purple-500"></i>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Active Users</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $stats['active_users'] }}</p>
                </div>
                <i class="fas fa-check-circle text-3xl text-green-500"></i>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Total Revenue</p>
                    <p class="text-2xl font-bold text-gray-800">€{{ number_format($stats['total_revenue'], 2) }}</p>
                </div>
                <i class="fas fa-euro-sign text-3xl text-yellow-500"></i>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Quick Actions</h2>
        <div class="flex flex-wrap gap-4">
            <a href="{{ route('admin.users.create') }}" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition">
                <i class="fas fa-plus mr-2"></i>Add Admin User
            </a>
            <a href="{{ route('admin.users.index') }}" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700 transition">
                <i class="fas fa-list mr-2"></i>View All Admins
            </a>
            <a href="{{ url('/') }}" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600 transition">
                <i class="fas fa-home mr-2"></i>View Frontend
            </a>
            <a href="{{ route('admin.activity.index') }}" class="bg-indigo-500 text-white px-4 py-2 rounded hover:bg-indigo-600 transition">
                <i class="fas fa-chart-line mr-2"></i>Activity Dashboard
            </a>
        </div>
    </div>

    @if(config('app.env') === 'local')
    <!-- Development Tools (Only in Local Environment) -->
    <div class="bg-yellow-50 border border-yellow-200 rounded-lg shadow p-6 mt-6">
        <h2 class="text-xl font-semibold text-yellow-800 mb-4">
            <i class="fas fa-wrench mr-2"></i>Development Tools
        </h2>
        <p class="text-sm text-yellow-700 mb-4">
            These tools are only available in development mode for testing and debugging.
        </p>
        <div class="flex flex-wrap gap-4">
            <a href="{{ route('admin.notifications.test.index') }}" class="bg-orange-500 text-white px-4 py-2 rounded hover:bg-orange-600 transition">
                <i class="fas fa-bell mr-2"></i>Test Notifications
            </a>
            <a href="{{ route('admin.settings.test.index') }}" class="bg-purple-500 text-white px-4 py-2 rounded hover:bg-purple-600 transition">
                <i class="fas fa-cogs mr-2"></i>Development Settings
            </a>
        </div>
    </div>
    @endif
</div>
@endsection