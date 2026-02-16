@extends('layouts.admin')

@section('title', 'Expiring Packages Report')

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="mb-6 flex justify-between items-center">
        <h1 class="text-3xl font-bold text-gray-800">Expiring Packages Report</h1>
        <a href="{{ route('admin.packages.index') }}" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600 transition">
            <i class="fas fa-arrow-left mr-2"></i>Back to Packages
        </a>
    </div>

    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <p class="text-gray-600">Packages expiring within the next 7 days</p>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Package</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Expiry Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Days Left</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sessions</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Auto-Renew</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Notified</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($packages as $package)
                    @php
                        $daysLeft = $package->getDaysUntilExpiry();
                    @endphp
                    <tr class="{{ $daysLeft <= 3 ? 'bg-red-50' : ($daysLeft <= 5 ? 'bg-yellow-50' : '') }}">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">{{ $package->user->name }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ $package->user->email }}</div>
                            @if($package->user->phone_number)
                                <div class="text-sm text-gray-500">{{ $package->user->phone_number }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ $package->name }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ $package->expiry_date->format('M d, Y') }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($daysLeft <= 3)
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                    {{ $daysLeft }} days
                                </span>
                            @elseif($daysLeft <= 5)
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                    {{ $daysLeft }} days
                                </span>
                            @else
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                    {{ $daysLeft }} days
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ $package->remaining_sessions }} left</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($package->auto_renew)
                                <span class="text-green-600"><i class="fas fa-check-circle"></i> Yes</span>
                            @else
                                <span class="text-gray-400"><i class="fas fa-times-circle"></i> No</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($package->last_notification_sent_at)
                                <div class="text-sm text-gray-900">{{ $package->last_notification_sent_at->format('M d') }}</div>
                                <div class="text-xs text-gray-500">{{ $package->notification_stage }}</div>
                            @else
                                <span class="text-gray-400">Never</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex gap-2">
                                <form action="{{ route('admin.packages.notify', $package) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="text-purple-600 hover:text-purple-900" title="Send Notification">
                                        <i class="fas fa-bell"></i>
                                    </button>
                                </form>
                                <a href="{{ route('admin.packages.renew.show', $package) }}" class="text-green-600 hover:text-green-900" title="Renew">
                                    <i class="fas fa-sync"></i>
                                </a>
                                <a href="{{ route('admin.packages.show', $package) }}" class="text-blue-600 hover:text-blue-900" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-6 py-4 text-center text-gray-500">No packages expiring in the next 7 days</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($packages->count() > 0)
        <div class="mt-6 bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4">Bulk Actions</h3>
            <div class="flex gap-4">
                <button onclick="sendBulkNotifications()" class="bg-purple-500 text-white px-4 py-2 rounded hover:bg-purple-600 transition">
                    <i class="fas fa-bell mr-2"></i>Send Notifications to All
                </button>
            </div>
        </div>
    @endif
</div>

<script>
function sendBulkNotifications() {
    if (confirm('Are you sure you want to send expiry notifications to all users with expiring packages?')) {
        // You can implement bulk notification logic here
        alert('This feature will be implemented to send bulk notifications');
    }
}
</script>
@endsection