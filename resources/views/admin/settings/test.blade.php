@extends('layouts.admin')

@section('title', 'Development Tools')

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
        <div class="flex items-center">
            <i class="fas fa-exclamation-triangle text-red-600 mr-2"></i>
            <h3 class="text-sm font-medium text-red-800">Development Environment Only</h3>
        </div>
        <p class="text-sm text-red-700 mt-1">
            These development tools are only available in local development mode. Use with caution as they can modify or delete data.
        </p>
    </div>

    <h1 class="text-3xl font-bold text-gray-800 mb-8">Development Tools</h1>
    
    <!-- Sample Data Generation -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Sample Data Generation</h2>
        <p class="text-gray-600 mb-4">Generate sample data for testing purposes. This will create users, packages, classes, and notifications.</p>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Users to Create</label>
                <input type="number" id="users-count" value="10" min="1" max="100" class="w-full border rounded px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Notifications to Create</label>
                <input type="number" id="notifications-count" value="5" min="1" max="50" class="w-full border rounded px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Packages to Create</label>
                <input type="number" id="packages-count" value="5" min="1" max="20" class="w-full border rounded px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Classes to Create</label>
                <input type="number" id="classes-count" value="5" min="1" max="20" class="w-full border rounded px-3 py-2">
            </div>
        </div>
        
        <button onclick="generateSampleData()" class="bg-blue-500 text-white px-6 py-2 rounded hover:bg-blue-600 transition">
            <i class="fas fa-database mr-2"></i>Generate Sample Data
        </button>
    </div>

    <!-- Notification Management -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Notification Management</h2>
        <p class="text-gray-600 mb-4">Manage and clear notifications for testing.</p>
        
        <div class="flex flex-wrap gap-3">
            <button onclick="clearNotifications('test')" class="bg-yellow-500 text-white px-4 py-2 rounded hover:bg-yellow-600 transition">
                <i class="fas fa-broom mr-2"></i>Clear Test Notifications
            </button>
            <button onclick="clearNotifications('sent')" class="bg-orange-500 text-white px-4 py-2 rounded hover:bg-orange-600 transition">
                <i class="fas fa-trash mr-2"></i>Clear Sent Notifications
            </button>
            <button onclick="clearNotifications('draft')" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600 transition">
                <i class="fas fa-file-alt mr-2"></i>Clear Draft Notifications
            </button>
            <button onclick="clearNotifications('all')" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600 transition">
                <i class="fas fa-exclamation-triangle mr-2"></i>Clear All Notifications
            </button>
        </div>
    </div>

    <!-- User State Management -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">User State Management</h2>
        <p class="text-gray-600 mb-4">Reset user states and activity for testing.</p>
        
        <div class="flex flex-wrap gap-3">
            <button onclick="resetUserStates('mark_all_read')" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600 transition">
                <i class="fas fa-check mr-2"></i>Mark All Notifications Read
            </button>
            <button onclick="resetUserStates('mark_all_unread')" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition">
                <i class="fas fa-envelope mr-2"></i>Mark All Notifications Unread
            </button>
            <button onclick="resetUserStates('reset_activity')" class="bg-purple-500 text-white px-4 py-2 rounded hover:bg-purple-600 transition">
                <i class="fas fa-history mr-2"></i>Reset Activity Logs
            </button>
            <button onclick="resetUserStates('activate_all')" class="bg-indigo-500 text-white px-4 py-2 rounded hover:bg-indigo-600 transition">
                <i class="fas fa-user-check mr-2"></i>Activate All Users
            </button>
        </div>
    </div>

    <!-- Package Expiry Testing -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Package Expiry Testing</h2>
        <p class="text-gray-600 mb-4">Test package expiry notifications and simulate expiring packages.</p>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Days Ahead to Check</label>
                <input type="number" id="days-ahead" value="7" min="1" max="30" class="w-full border rounded px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Simulate Expiry</label>
                <select id="simulate-expiry" class="w-full border rounded px-3 py-2">
                    <option value="false">No - Use existing packages</option>
                    <option value="true">Yes - Create expiring packages</option>
                </select>
            </div>
        </div>
        
        <button onclick="triggerPackageExpiry()" class="bg-red-500 text-white px-6 py-2 rounded hover:bg-red-600 transition">
            <i class="fas fa-clock mr-2"></i>Trigger Package Expiry Notifications
        </button>
    </div>

    <!-- System Statistics -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">System Statistics</h2>
        <div id="stats-container" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Statistics will be loaded here -->
        </div>
        <button onclick="refreshStats()" class="mt-4 bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600 transition">
            <i class="fas fa-sync mr-2"></i>Refresh Statistics
        </button>
    </div>

    <!-- Activity Log -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Activity Log</h2>
        <div id="activity-log" class="space-y-2 max-h-96 overflow-y-auto">
            <!-- Activity log entries will be added here -->
        </div>
        <button onclick="clearActivityLog()" class="mt-4 bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600 transition">
            <i class="fas fa-eraser mr-2"></i>Clear Log
        </button>
    </div>
</div>

<script>
// Activity log function
function addToActivityLog(message, type = 'info') {
    const log = document.getElementById('activity-log');
    const entry = document.createElement('div');
    entry.className = `p-3 rounded text-sm ${type === 'error' ? 'bg-red-100 text-red-800' : type === 'success' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800'}`;
    entry.innerHTML = `<span class="font-medium">${new Date().toLocaleTimeString()}</span> - ${message}`;
    log.insertBefore(entry, log.firstChild);
    
    // Keep only last 30 entries
    while (log.children.length > 30) {
        log.removeChild(log.lastChild);
    }
}

// Clear activity log
function clearActivityLog() {
    document.getElementById('activity-log').innerHTML = '';
    addToActivityLog('Activity log cleared');
}

// Generate sample data
async function generateSampleData() {
    const data = {
        users_count: parseInt(document.getElementById('users-count').value),
        notifications_count: parseInt(document.getElementById('notifications-count').value),
        packages_count: parseInt(document.getElementById('packages-count').value),
        classes_count: parseInt(document.getElementById('classes-count').value)
    };
    
    try {
        addToActivityLog('Generating sample data...');
        
        const response = await fetch('/admin/settings/test/sample-data', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (response.ok) {
            addToActivityLog(`✓ Sample data generated successfully:`, 'success');
            addToActivityLog(`  - Users: ${result.created.users}`, 'success');
            addToActivityLog(`  - Packages: ${result.created.packages}`, 'success');
            addToActivityLog(`  - Classes: ${result.created.classes}`, 'success');
            addToActivityLog(`  - Notifications: ${result.created.notifications}`, 'success');
            addToActivityLog(`  - Instructors: ${result.created.instructors}`, 'success');
        } else {
            addToActivityLog(`✗ Failed to generate sample data: ${result.error}`, 'error');
        }
    } catch (error) {
        addToActivityLog(`✗ Error generating sample data: ${error.message}`, 'error');
    }
    
    refreshStats();
}

// Clear notifications
async function clearNotifications(type) {
    const confirmMessage = type === 'all' ? 
        'Are you sure you want to clear ALL notifications? This cannot be undone!' :
        `Are you sure you want to clear ${type} notifications?`;
    
    if (!confirm(confirmMessage)) {
        return;
    }
    
    try {
        addToActivityLog(`Clearing ${type} notifications...`);
        
        const response = await fetch('/admin/settings/test/notifications', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ type })
        });
        
        const result = await response.json();
        
        if (response.ok) {
            addToActivityLog(`✓ Cleared ${result.deleted_count} ${type} notifications`, 'success');
        } else {
            addToActivityLog(`✗ Failed to clear notifications: ${result.error}`, 'error');
        }
    } catch (error) {
        addToActivityLog(`✗ Error clearing notifications: ${error.message}`, 'error');
    }
    
    refreshStats();
}

// Reset user states
async function resetUserStates(action) {
    const confirmMessage = `Are you sure you want to ${action.replace('_', ' ')}?`;
    
    if (!confirm(confirmMessage)) {
        return;
    }
    
    try {
        addToActivityLog(`Performing ${action}...`);
        
        const response = await fetch('/admin/settings/test/reset-users', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ action })
        });
        
        const result = await response.json();
        
        if (response.ok) {
            addToActivityLog(`✓ ${action} completed successfully (${result.affected_count} affected)`, 'success');
        } else {
            addToActivityLog(`✗ Failed to ${action}: ${result.error}`, 'error');
        }
    } catch (error) {
        addToActivityLog(`✗ Error performing ${action}: ${error.message}`, 'error');
    }
    
    refreshStats();
}

// Trigger package expiry
async function triggerPackageExpiry() {
    const data = {
        days_ahead: parseInt(document.getElementById('days-ahead').value),
        simulate_expiry: document.getElementById('simulate-expiry').value === 'true'
    };
    
    try {
        addToActivityLog('Triggering package expiry notifications...');
        
        const response = await fetch('/admin/settings/test/package-expiry', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (response.ok) {
            addToActivityLog(`✓ Package expiry process completed:`, 'success');
            if (result.results.simulated_packages) {
                addToActivityLog(`  - Simulated packages created: ${result.results.simulated_packages}`, 'success');
            }
            addToActivityLog(`  - Expiring packages found: ${result.results.expiring_packages_found}`, 'success');
            addToActivityLog(`  - Notifications sent: ${result.results.notifications_sent}`, 'success');
        } else {
            addToActivityLog(`✗ Failed to trigger package expiry: ${result.error}`, 'error');
        }
    } catch (error) {
        addToActivityLog(`✗ Error triggering package expiry: ${error.message}`, 'error');
    }
    
    refreshStats();
}

// Refresh statistics
async function refreshStats() {
    try {
        const response = await fetch('/admin/settings/test/stats', {
            method: 'GET',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });
        
        if (response.ok) {
            const data = await response.json();
            const container = document.getElementById('stats-container');
            
            container.innerHTML = `
                <div class="text-center">
                    <div class="text-2xl font-bold text-blue-600">${data.stats.users.total}</div>
                    <div class="text-sm text-gray-600">Total Users</div>
                    <div class="text-xs text-gray-500">${data.stats.users.active} active</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-green-600">${data.stats.notifications.total}</div>
                    <div class="text-sm text-gray-600">Total Notifications</div>
                    <div class="text-xs text-gray-500">${data.stats.notifications.sent} sent</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-purple-600">${data.stats.packages.total}</div>
                    <div class="text-sm text-gray-600">Total Packages</div>
                    <div class="text-xs text-gray-500">${data.stats.packages.user_packages} user packages</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-orange-600">${data.stats.classes.total}</div>
                    <div class="text-sm text-gray-600">Total Classes</div>
                    <div class="text-xs text-gray-500">${data.stats.classes.bookings} bookings</div>
                </div>
            `;
        }
    } catch (error) {
        console.error('Error refreshing stats:', error);
    }
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    refreshStats();
    addToActivityLog('Development tools interface loaded', 'info');
});
</script>
@endsection