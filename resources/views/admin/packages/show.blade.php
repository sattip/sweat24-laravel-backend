@extends('layouts.admin')

@section('title', 'Package Details')

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="mb-6 flex justify-between items-center">
        <h1 class="text-3xl font-bold text-gray-800">Package Details</h1>
        <div class="flex gap-2">
            <button onclick="openEditModal()" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition">
                <i class="fas fa-edit mr-2"></i>Edit Package
            </button>
            <a href="{{ route('admin.packages.index') }}" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600 transition">
                <i class="fas fa-arrow-left mr-2"></i>Back to List
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Package Information -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Package Information</h2>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-600">User</p>
                        <p class="font-semibold">{{ $userPackage->user->name }}</p>
                        <p class="text-sm text-gray-500">{{ $userPackage->user->email }}</p>
                    </div>
                    
                    <div>
                        <p class="text-sm text-gray-600">Package</p>
                        <p class="font-semibold">{{ $userPackage->name }}</p>
                        <p class="text-sm text-gray-500">{{ $userPackage->package->type }}</p>
                    </div>
                    
                    <div>
                        <p class="text-sm text-gray-600">Status</p>
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
                    </div>
                    
                    <div>
                        <p class="text-sm text-gray-600">Auto-Renew</p>
                        @if($userPackage->auto_renew)
                            <span class="text-green-600"><i class="fas fa-check-circle"></i> Enabled</span>
                        @else
                            <span class="text-gray-400"><i class="fas fa-times-circle"></i> Disabled</span>
                        @endif
                    </div>
                    
                    <div>
                        <p class="text-sm text-gray-600">Sessions</p>
                        <p class="font-semibold">{{ $userPackage->remaining_sessions }} / {{ $userPackage->total_sessions }}</p>
                        <div class="w-full bg-gray-200 rounded-full h-2 mt-1">
                            <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $stats['usage_percentage'] }}%"></div>
                        </div>
                    </div>
                    
                    <div>
                        <p class="text-sm text-gray-600">Usage</p>
                        <p class="font-semibold">{{ $stats['usage_percentage'] }}%</p>
                    </div>
                    
                    <div>
                        <p class="text-sm text-gray-600">Assigned Date</p>
                        <p class="font-semibold">{{ $userPackage->assigned_date->format('M d, Y') }}</p>
                    </div>
                    
                    <div>
                        <p class="text-sm text-gray-600">Expiry Date</p>
                        <p class="font-semibold">{{ $userPackage->expiry_date->format('M d, Y') }}</p>
                        @if($stats['days_until_expiry'] !== null)
                            @if($stats['days_until_expiry'] > 0)
                                <p class="text-sm text-yellow-600">{{ $stats['days_until_expiry'] }} days remaining</p>
                            @else
                                <p class="text-sm text-red-600">Expired {{ abs($stats['days_until_expiry']) }} days ago</p>
                            @endif
                        @endif
                    </div>
                </div>

                @if($userPackage->is_frozen)
                    <div class="mt-4 p-4 bg-blue-50 rounded-lg">
                        <p class="text-sm text-blue-800">
                            <i class="fas fa-snowflake mr-2"></i>
                            Package frozen since {{ $userPackage->frozen_at->format('M d, Y H:i') }}
                            @if($userPackage->freeze_duration_days)
                                (Max duration: {{ $userPackage->freeze_duration_days }} days)
                            @endif
                        </p>
                    </div>
                @endif
            </div>

            <!-- Actions -->
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Actions</h2>
                
                <div class="flex flex-wrap gap-4">
                    @if($userPackage->status !== 'frozen')
                        <button onclick="showFreezeModal()" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition">
                            <i class="fas fa-snowflake mr-2"></i>Freeze Package
                        </button>
                    @else
                        <form action="{{ route('admin.packages.unfreeze', $userPackage) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600 transition">
                                <i class="fas fa-fire mr-2"></i>Unfreeze Package
                            </button>
                        </form>
                    @endif
                    
                    <a href="{{ route('admin.packages.renew.show', $userPackage) }}" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600 transition">
                        <i class="fas fa-sync mr-2"></i>Renew Package
                    </a>
                    
                    @if($userPackage->isExpiringSoon() || $userPackage->isExpired())
                        <form action="{{ route('admin.packages.notify', $userPackage) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="bg-purple-500 text-white px-4 py-2 rounded hover:bg-purple-600 transition">
                                <i class="fas fa-bell mr-2"></i>Send Notification
                            </button>
                        </form>
                    @endif
                    
                    <button onclick="showEditModal()" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600 transition">
                        <i class="fas fa-edit mr-2"></i>Edit Settings
                    </button>
                </div>
            </div>

            <!-- Package History -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Package History</h2>
                
                <div class="space-y-4">
                    @forelse($userPackage->history->sortByDesc('created_at') as $history)
                        <div class="border-l-4 border-gray-200 pl-4">
                            <div class="flex justify-between">
                                <div>
                                    <p class="font-semibold text-gray-800">{{ ucfirst($history->action) }}</p>
                                    @if($history->notes)
                                        <p class="text-sm text-gray-600">{{ json_encode($history->notes) }}</p>
                                    @endif
                                    @if($history->performedBy)
                                        <p class="text-xs text-gray-500">by {{ $history->performedBy->name }}</p>
                                    @endif
                                </div>
                                <p class="text-sm text-gray-500">{{ $history->created_at->format('M d, Y H:i') }}</p>
                            </div>
                        </div>
                    @empty
                        <p class="text-gray-500">No history available</p>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Notification Logs -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Notification Logs</h2>
                
                <div class="space-y-3">
                    @forelse($userPackage->notificationLogs->sortByDesc('created_at')->take(10) as $log)
                        <div class="border-b pb-3">
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="text-sm font-semibold">{{ ucfirst(str_replace('_', ' ', $log->notification_type)) }}</p>
                                    <p class="text-xs text-gray-500">{{ $log->channel }}</p>
                                </div>
                                @if($log->sent_successfully)
                                    <span class="text-green-500"><i class="fas fa-check-circle"></i></span>
                                @else
                                    <span class="text-red-500" title="{{ $log->error_message }}"><i class="fas fa-times-circle"></i></span>
                                @endif
                            </div>
                            <p class="text-xs text-gray-500 mt-1">{{ $log->created_at->format('M d, Y H:i') }}</p>
                        </div>
                    @empty
                        <p class="text-gray-500 text-sm">No notifications sent yet</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Freeze Modal -->
<div id="freezeModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <h3 class="text-lg font-bold text-gray-900 mb-4">Freeze Package</h3>
        <form action="{{ route('admin.packages.freeze', $userPackage) }}" method="POST">
            @csrf
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">Duration (days)</label>
                <input type="number" name="duration_days" min="1" max="90" 
                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                    placeholder="Leave empty for indefinite">
                <p class="text-xs text-gray-500 mt-1">Maximum 90 days. Leave empty for indefinite freeze.</p>
            </div>
            <div class="flex justify-end gap-4">
                <button type="button" onclick="hideFreezeModal()" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">Cancel</button>
                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Freeze</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <h3 class="text-lg font-bold text-gray-900 mb-4">Edit Package Settings</h3>
        <form action="{{ route('admin.packages.update', $userPackage) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">Auto-Renew</label>
                <select name="auto_renew" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    <option value="0" {{ !$userPackage->auto_renew ? 'selected' : '' }}>Disabled</option>
                    <option value="1" {{ $userPackage->auto_renew ? 'selected' : '' }}>Enabled</option>
                </select>
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">Expiry Date</label>
                <input type="date" name="expiry_date" value="{{ $userPackage->expiry_date->format('Y-m-d') }}" 
                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">Remaining Sessions</label>
                <input type="number" name="remaining_sessions" value="{{ $userPackage->remaining_sessions }}" min="0"
                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
            </div>
            <div class="flex justify-end gap-4">
                <button type="button" onclick="hideEditModal()" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">Cancel</button>
                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Update</button>
            </div>
        </form>
    </div>
</div>

<script>
function showFreezeModal() {
    document.getElementById('freezeModal').classList.remove('hidden');
}

function hideFreezeModal() {
    document.getElementById('freezeModal').classList.add('hidden');
}

function openEditModal() {
    document.getElementById('editModal').classList.remove('hidden');
}

function hideEditModal() {
    document.getElementById('editModal').classList.add('hidden');
}
</script>
@endsection