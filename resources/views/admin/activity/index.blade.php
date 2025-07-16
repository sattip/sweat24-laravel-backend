@extends('layouts.admin')

@section('title', 'Activity Dashboard')

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Recent Activity Dashboard</h1>
        <div class="flex space-x-2">
            <button id="toggle-realtime" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600 transition">
                <i class="fas fa-play mr-2"></i>Real-time: OFF
            </button>
            <button onclick="exportActivities('csv')" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition">
                <i class="fas fa-download mr-2"></i>Export CSV
            </button>
            <button onclick="exportActivities('json')" class="bg-purple-500 text-white px-4 py-2 rounded hover:bg-purple-600 transition">
                <i class="fas fa-download mr-2"></i>Export JSON
            </button>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Total Activities</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $stats['total_activities'] }}</p>
                </div>
                <i class="fas fa-chart-line text-3xl text-blue-500"></i>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Today's Activities</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $stats['today_total'] }}</p>
                </div>
                <i class="fas fa-calendar-day text-3xl text-green-500"></i>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">New Registrations</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $stats['activity_counts']['registration'] ?? 0 }}</p>
                </div>
                <i class="fas fa-user-plus text-3xl text-purple-500"></i>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Bookings</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $stats['activity_counts']['booking'] ?? 0 }}</p>
                </div>
                <i class="fas fa-calendar-check text-3xl text-indigo-500"></i>
            </div>
        </div>
    </div>

    <!-- Activity Type Breakdown -->
    <div class="bg-white rounded-lg shadow p-6 mb-8">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Activity Breakdown</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
            @foreach($activityTypes as $type => $label)
                <div class="text-center">
                    <div class="text-2xl font-bold text-gray-800">{{ $stats['activity_counts'][$type] ?? 0 }}</div>
                    <div class="text-sm text-gray-600">{{ $label }}</div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-6 mb-8">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Filters</h2>
        <form method="GET" action="{{ route('admin.activity.index') }}" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Activity Type</label>
                <select name="activity_type" class="w-full border border-gray-300 rounded-md px-3 py-2">
                    <option value="">All Types</option>
                    @foreach($activityTypes as $type => $label)
                        <option value="{{ $type }}" {{ $activityType === $type ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">User</label>
                <select name="user_id" class="w-full border border-gray-300 rounded-md px-3 py-2">
                    <option value="">All Users</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" {{ $userId == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Date From</label>
                <input type="date" name="date_from" value="{{ $dateFrom }}" class="w-full border border-gray-300 rounded-md px-3 py-2">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Date To</label>
                <input type="date" name="date_to" value="{{ $dateTo }}" class="w-full border border-gray-300 rounded-md px-3 py-2">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                <input type="text" name="search" value="{{ $search }}" placeholder="Search activities..." class="w-full border border-gray-300 rounded-md px-3 py-2">
            </div>
            
            <div class="flex items-end space-x-2">
                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition">
                    <i class="fas fa-filter mr-2"></i>Filter
                </button>
                <a href="{{ route('admin.activity.index') }}" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600 transition">
                    <i class="fas fa-times mr-2"></i>Clear
                </a>
            </div>
        </form>
    </div>

    <!-- Activity Feed -->
    <div class="bg-white rounded-lg shadow">
        <div class="p-6 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-800">Activity Feed</h2>
        </div>
        
        <div id="activity-feed" class="divide-y divide-gray-200">
            @forelse($activities as $activity)
                <div class="p-6 hover:bg-gray-50 transition activity-item" data-id="{{ $activity->id }}">
                    <div class="flex items-start space-x-4">
                        <div class="flex-shrink-0">
                            <div class="w-10 h-10 bg-{{ $activity->activity_color }}-100 rounded-full flex items-center justify-center">
                                <i class="{{ $activity->activity_icon }} text-{{ $activity->activity_color }}-600"></i>
                            </div>
                        </div>
                        
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-2">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $activity->activity_color }}-100 text-{{ $activity->activity_color }}-800">
                                        {{ $activity->activity_type_label }}
                                    </span>
                                    <span class="text-sm text-gray-500">{{ $activity->created_at->setTimezone(config('app.timezone'))->diffForHumans() }}</span>
                                </div>
                                
                                <div class="flex items-center space-x-2">
                                    @if($activity->subject)
                                        <button onclick="showQuickActions({{ $activity->id }}, '{{ $activity->model_type }}', {{ $activity->model_id }})" 
                                                class="text-blue-600 hover:text-blue-800 text-sm">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                    @endif
                                </div>
                            </div>
                            
                            <p class="mt-1 text-sm text-gray-900">{{ $activity->action }}</p>
                            
                            <div class="mt-2 flex items-center space-x-4 text-sm text-gray-500">
                                <span><i class="fas fa-user mr-1"></i>{{ $activity->user->name ?? 'System' }}</span>
                                <span><i class="fas fa-globe mr-1"></i>{{ $activity->ip_address }}</span>
                                @if($activity->created_at->format('Y-m-d') === now()->format('Y-m-d'))
                                    <span class="text-green-600 font-medium">Today</span>
                                @endif
                            </div>
                            
                            @if($activity->properties && count($activity->properties) > 0)
                                <div class="mt-2">
                                    <button onclick="toggleProperties({{ $activity->id }})" class="text-xs text-blue-600 hover:text-blue-800">
                                        <i class="fas fa-chevron-down mr-1"></i>Show Details
                                    </button>
                                    <div id="properties-{{ $activity->id }}" class="hidden mt-2 bg-gray-50 rounded p-3">
                                        <pre class="text-xs text-gray-700">{{ json_encode($activity->properties, JSON_PRETTY_PRINT) }}</pre>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="p-6 text-center text-gray-500">
                    <i class="fas fa-inbox text-4xl mb-4"></i>
                    <p>No activities found.</p>
                </div>
            @endforelse
        </div>
        
        @if($activities->hasPages())
            <div class="p-6 border-t border-gray-200">
                {{ $activities->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Quick Actions Modal -->
<div id="quick-actions-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <h3 class="text-lg leading-6 font-medium text-gray-900">Quick Actions</h3>
            <div class="mt-2 px-7 py-3">
                <div id="quick-actions-content">
                    <!-- Actions will be loaded here -->
                </div>
            </div>
            <div class="items-center px-4 py-3">
                <button onclick="closeQuickActionsModal()" class="px-4 py-2 bg-gray-500 text-white text-base font-medium rounded-md w-full shadow-sm hover:bg-gray-600">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let realtimeEnabled = false;
let realtimeInterval = null;
let lastActivityId = {{ $activities->first()->id ?? 0 }};

// Real-time toggle
document.getElementById('toggle-realtime').addEventListener('click', function() {
    realtimeEnabled = !realtimeEnabled;
    const button = this;
    
    if (realtimeEnabled) {
        button.innerHTML = '<i class="fas fa-pause mr-2"></i>Real-time: ON';
        button.classList.remove('bg-green-500', 'hover:bg-green-600');
        button.classList.add('bg-red-500', 'hover:bg-red-600');
        
        // Start polling
        realtimeInterval = setInterval(pollActivities, 5000);
    } else {
        button.innerHTML = '<i class="fas fa-play mr-2"></i>Real-time: OFF';
        button.classList.remove('bg-red-500', 'hover:bg-red-600');
        button.classList.add('bg-green-500', 'hover:bg-green-600');
        
        // Stop polling
        if (realtimeInterval) {
            clearInterval(realtimeInterval);
        }
    }
});

// Poll for new activities
function pollActivities() {
    fetch(`{{ route('admin.activity.realtime') }}?last_id=${lastActivityId}`)
        .then(response => response.json())
        .then(data => {
            if (data.activities.length > 0) {
                const feed = document.getElementById('activity-feed');
                
                // Add new activities to the top
                data.activities.forEach(activity => {
                    const activityHtml = createActivityHtml(activity);
                    feed.insertAdjacentHTML('afterbegin', activityHtml);
                });
                
                // Update last activity ID
                lastActivityId = data.last_id;
                
                // Remove old activities to keep the feed manageable
                const items = feed.querySelectorAll('.activity-item');
                if (items.length > 50) {
                    for (let i = 50; i < items.length; i++) {
                        items[i].remove();
                    }
                }
            }
        })
        .catch(error => {
            console.error('Error polling activities:', error);
        });
}

// Create activity HTML
function createActivityHtml(activity) {
    return `
        <div class="p-6 hover:bg-gray-50 transition activity-item animate-pulse" data-id="${activity.id}">
            <div class="flex items-start space-x-4">
                <div class="flex-shrink-0">
                    <div class="w-10 h-10 bg-${activity.activity_color}-100 rounded-full flex items-center justify-center">
                        <i class="${activity.activity_icon} text-${activity.activity_color}-600"></i>
                    </div>
                </div>
                
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-${activity.activity_color}-100 text-${activity.activity_color}-800">
                                ${activity.activity_type_label}
                            </span>
                            <span class="text-sm text-gray-500">${activity.created_at_human}</span>
                        </div>
                    </div>
                    
                    <p class="mt-1 text-sm text-gray-900">${activity.action}</p>
                    
                    <div class="mt-2 flex items-center space-x-4 text-sm text-gray-500">
                        <span><i class="fas fa-user mr-1"></i>${activity.user.name}</span>
                        <span class="text-green-600 font-medium">Just now</span>
                    </div>
                </div>
            </div>
        </div>
    `;
}

// Export functions
function exportActivities(format) {
    const params = new URLSearchParams(window.location.search);
    params.set('format', format);
    
    window.location.href = `{{ route('admin.activity.export') }}?${params.toString()}`;
}

// Toggle properties
function toggleProperties(activityId) {
    const element = document.getElementById(`properties-${activityId}`);
    const button = element.previousElementSibling;
    
    if (element.classList.contains('hidden')) {
        element.classList.remove('hidden');
        button.innerHTML = '<i class="fas fa-chevron-up mr-1"></i>Hide Details';
    } else {
        element.classList.add('hidden');
        button.innerHTML = '<i class="fas fa-chevron-down mr-1"></i>Show Details';
    }
}

// Quick actions
function showQuickActions(activityId, modelType, modelId) {
    const modal = document.getElementById('quick-actions-modal');
    const content = document.getElementById('quick-actions-content');
    
    // Generate quick actions based on model type
    let actions = '';
    
    if (modelType === 'App\\Models\\User') {
        actions = `
            <a href="/admin/users/${modelId}/edit" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded">
                <i class="fas fa-edit mr-2"></i>Edit User
            </a>
        `;
    } else if (modelType === 'App\\Models\\Booking') {
        actions = `
            <button onclick="viewBooking(${modelId})" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded">
                <i class="fas fa-eye mr-2"></i>View Booking
            </button>
        `;
    } else if (modelType === 'App\\Models\\UserPackage') {
        actions = `
            <a href="/admin/packages/${modelId}" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded">
                <i class="fas fa-eye mr-2"></i>View Package
            </a>
        `;
    }
    
    if (!actions) {
        actions = '<p class="text-gray-500 text-sm">No quick actions available for this item.</p>';
    }
    
    content.innerHTML = actions;
    modal.classList.remove('hidden');
}

function closeQuickActionsModal() {
    document.getElementById('quick-actions-modal').classList.add('hidden');
}

// Remove animation after 3 seconds
setTimeout(() => {
    document.querySelectorAll('.animate-pulse').forEach(el => {
        el.classList.remove('animate-pulse');
    });
}, 3000);
</script>
@endsection