@extends('layouts.admin')

@section('title', 'Package Management')

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="mb-6 flex justify-between items-center">
        <h1 class="text-3xl font-bold text-gray-800">Package Management</h1>
        <div class="flex gap-4">
            <a href="{{ route('admin.packages.bulk.index') }}" class="bg-purple-500 text-white px-4 py-2 rounded hover:bg-purple-600 transition">
                <i class="fas fa-layer-group mr-2"></i>Bulk Operations
            </a>
            <a href="{{ route('admin.packages.expiring') }}" class="bg-yellow-500 text-white px-4 py-2 rounded hover:bg-yellow-600 transition">
                <i class="fas fa-clock mr-2"></i>Expiring Report
            </a>
            <a href="{{ route('admin.packages.create') }}" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition">
                <i class="fas fa-plus mr-2"></i>Assign Package
            </a>
        </div>
    </div>

    <!-- Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Active</p>
                    <p class="text-2xl font-bold text-green-600">{{ $stats['total_active'] }}</p>
                </div>
                <i class="fas fa-check-circle text-3xl text-green-500"></i>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Expiring Soon</p>
                    <p class="text-2xl font-bold text-yellow-600">{{ $stats['expiring_soon'] }}</p>
                </div>
                <i class="fas fa-exclamation-triangle text-3xl text-yellow-500"></i>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Expired</p>
                    <p class="text-2xl font-bold text-red-600">{{ $stats['expired'] }}</p>
                </div>
                <i class="fas fa-times-circle text-3xl text-red-500"></i>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Frozen</p>
                    <p class="text-2xl font-bold text-blue-600">{{ $stats['frozen'] }}</p>
                </div>
                <i class="fas fa-snowflake text-3xl text-blue-500"></i>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Auto-Renew</p>
                    <p class="text-2xl font-bold text-purple-600">{{ $stats['auto_renew_enabled'] }}</p>
                </div>
                <i class="fas fa-sync text-3xl text-purple-500"></i>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-4 mb-6">
        <form method="GET" action="{{ route('admin.packages.index') }}" class="flex gap-4">
            <div class="flex-1">
                <input type="text" name="search" value="{{ request('search') }}" 
                    placeholder="Search by user name or email..." 
                    class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <select name="status" class="px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="all">All Status</option>
                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                <option value="expiring_soon" {{ request('status') == 'expiring_soon' ? 'selected' : '' }}>Expiring Soon</option>
                <option value="expired" {{ request('status') == 'expired' ? 'selected' : '' }}>Expired</option>
                <option value="frozen" {{ request('status') == 'frozen' ? 'selected' : '' }}>Frozen</option>
            </select>
            <label class="flex items-center">
                <input type="checkbox" name="expiring_soon" value="1" {{ request('expiring_soon') ? 'checked' : '' }} class="mr-2">
                <span>Expiring in 7 days</span>
            </label>
            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition">
                <i class="fas fa-search mr-2"></i>Filter
            </button>
            <a href="{{ route('admin.packages.index') }}" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600 transition">
                <i class="fas fa-times mr-2"></i>Clear
            </a>
        </form>
    </div>

    <!-- Package List -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Package</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sessions</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Expiry Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Auto-Renew</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($userPackages as $userPackage)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div>
                                <div class="text-sm font-medium text-gray-900">{{ $userPackage->user->name }}</div>
                                <div class="text-sm text-gray-500">{{ $userPackage->user->email }}</div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ $userPackage->name }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">
                                {{ $userPackage->remaining_sessions }} / {{ $userPackage->total_sessions }}
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2 mt-1">
                                <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $userPackage->total_sessions > 0 ? (($userPackage->total_sessions - $userPackage->remaining_sessions) / $userPackage->total_sessions * 100) : 0 }}%"></div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ $userPackage->expiry_date->format('M d, Y') }}</div>
                            @php
                                $daysUntilExpiry = $userPackage->getDaysUntilExpiry();
                            @endphp
                            @if($daysUntilExpiry !== null && $daysUntilExpiry <= 7 && $daysUntilExpiry > 0)
                                <div class="text-xs text-yellow-600">{{ $daysUntilExpiry }} days left</div>
                            @elseif($daysUntilExpiry !== null && $daysUntilExpiry <= 0)
                                <div class="text-xs text-red-600">Expired</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @switch($userPackage->status)
                                @case('active')
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Active</span>
                                    @break
                                @case('expiring_soon')
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Expiring Soon</span>
                                    @break
                                @case('expired')
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Expired</span>
                                    @break
                                @case('frozen')
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">Frozen</span>
                                    @break
                                @default
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">{{ ucfirst($userPackage->status) }}</span>
                            @endswitch
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($userPackage->auto_renew)
                                <span class="text-green-600"><i class="fas fa-check-circle"></i> Yes</span>
                            @else
                                <span class="text-gray-400"><i class="fas fa-times-circle"></i> No</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex gap-2">
                                <a href="{{ route('admin.packages.show', $userPackage) }}" class="text-blue-600 hover:text-blue-900" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @if($userPackage->status !== 'frozen')
                                    <form action="{{ route('admin.packages.freeze', $userPackage) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="text-blue-600 hover:text-blue-900" title="Freeze Package">
                                            <i class="fas fa-snowflake"></i>
                                        </button>
                                    </form>
                                @else
                                    <form action="{{ route('admin.packages.unfreeze', $userPackage) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="text-red-600 hover:text-red-900" title="Unfreeze Package">
                                            <i class="fas fa-fire"></i>
                                        </button>
                                    </form>
                                @endif
                                <a href="{{ route('admin.packages.renew.show', $userPackage) }}" class="text-green-600 hover:text-green-900" title="Renew Package">
                                    <i class="fas fa-sync"></i>
                                </a>
                                @if($userPackage->isExpiringSoon() || $userPackage->isExpired())
                                    <form action="{{ route('admin.packages.notify', $userPackage) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="text-purple-600 hover:text-purple-900" title="Send Notification">
                                            <i class="fas fa-bell"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-4 text-center text-gray-500">No packages found</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="mt-4">
        {{ $userPackages->links() }}
    </div>
</div>
@endsection