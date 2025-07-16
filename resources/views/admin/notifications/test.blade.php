@extends('layouts.admin')

@section('title', 'Test Notifications')

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
        <div class="flex items-center">
            <i class="fas fa-exclamation-triangle text-yellow-600 mr-2"></i>
            <h3 class="text-sm font-medium text-yellow-800">Development Mode Only</h3>
        </div>
        <p class="text-sm text-yellow-700 mt-1">
            This testing interface is only available in development mode. All notifications sent through this interface will be marked as test notifications.
        </p>
    </div>

    <h1 class="text-3xl font-bold text-gray-800 mb-8">Test Notifications</h1>
    
    <!-- Quick Actions -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <!-- Individual Test Notifications -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Individual Test Notifications</h2>
            
            <div class="space-y-3">
                <button onclick="sendTestNotification('in-app')" 
                        class="w-full bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition">
                    <i class="fas fa-bell mr-2"></i>Test In-App Notification
                </button>
                
                <button onclick="sendTestNotification('email')" 
                        class="w-full bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600 transition">
                    <i class="fas fa-envelope mr-2"></i>Test Email Notification
                </button>
                
                <button onclick="sendTestNotification('sms')" 
                        class="w-full bg-purple-500 text-white px-4 py-2 rounded hover:bg-purple-600 transition">
                    <i class="fas fa-sms mr-2"></i>Test SMS Notification
                </button>
            </div>
        </div>

        <!-- Bulk Notifications -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Bulk Test Notifications</h2>
            
            <div class="space-y-3">
                <button onclick="openBulkModal()" 
                        class="w-full bg-orange-500 text-white px-4 py-2 rounded hover:bg-orange-600 transition">
                    <i class="fas fa-users mr-2"></i>Create Bulk Notification
                </button>
                
                <button onclick="openTargetedModal()" 
                        class="w-full bg-indigo-500 text-white px-4 py-2 rounded hover:bg-indigo-600 transition">
                    <i class="fas fa-target mr-2"></i>Create Targeted Notification
                </button>
            </div>
        </div>

        <!-- Cleanup Actions -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Cleanup Actions</h2>
            
            <div class="space-y-3">
                <button onclick="clearTestNotifications()" 
                        class="w-full bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600 transition">
                    <i class="fas fa-trash mr-2"></i>Clear Test Notifications
                </button>
                
                <button onclick="refreshStats()" 
                        class="w-full bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600 transition">
                    <i class="fas fa-sync mr-2"></i>Refresh Stats
                </button>
            </div>
        </div>
    </div>

    <!-- Statistics -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Test Statistics</h2>
        <div id="stats-container" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="text-center">
                <div class="text-2xl font-bold text-blue-600" id="total-notifications">-</div>
                <div class="text-sm text-gray-600">Total Notifications</div>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold text-green-600" id="sent-notifications">-</div>
                <div class="text-sm text-gray-600">Sent Notifications</div>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold text-yellow-600" id="test-notifications">-</div>
                <div class="text-sm text-gray-600">Test Notifications</div>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold text-purple-600" id="total-users">-</div>
                <div class="text-sm text-gray-600">Total Users</div>
            </div>
        </div>
    </div>

    <!-- Activity Log -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Activity Log</h2>
        <div id="activity-log" class="space-y-2 max-h-96 overflow-y-auto">
            <!-- Activity log entries will be added here -->
        </div>
    </div>
</div>

<!-- Bulk Notification Modal -->
<div id="bulk-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg max-w-md w-full p-6">
            <h3 class="text-lg font-semibold mb-4">Create Bulk Notification</h3>
            <form id="bulk-form">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Title</label>
                    <input type="text" name="title" class="w-full border rounded px-3 py-2" required>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Message</label>
                    <textarea name="message" rows="3" class="w-full border rounded px-3 py-2" required></textarea>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">User Type</label>
                    <select name="user_type" class="w-full border rounded px-3 py-2">
                        <option value="all">All Users</option>
                        <option value="active">Active Users</option>
                        <option value="admin">Admin Users</option>
                        <option value="member">Member Users</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Channels</label>
                    <div class="space-y-2">
                        <label class="flex items-center">
                            <input type="checkbox" name="channels[]" value="in_app" checked class="mr-2">
                            In-App
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="channels[]" value="email" class="mr-2">
                            Email
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="channels[]" value="sms" class="mr-2">
                            SMS
                        </label>
                    </div>
                </div>
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeBulkModal()" class="px-4 py-2 text-gray-600 hover:text-gray-800">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">Send Bulk Notification</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Targeted Notification Modal -->
<div id="targeted-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg max-w-md w-full p-6">
            <h3 class="text-lg font-semibold mb-4">Create Targeted Notification</h3>
            <form id="targeted-form">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Title</label>
                    <input type="text" name="title" class="w-full border rounded px-3 py-2" required>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Message</label>
                    <textarea name="message" rows="3" class="w-full border rounded px-3 py-2" required></textarea>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Target Group</label>
                    <select name="target_group" class="w-full border rounded px-3 py-2">
                        <option value="recent_members">Recent Members (Last 7 days)</option>
                        <option value="expiring_packages">Users with Expiring Packages</option>
                        <option value="inactive_users">Inactive Users (30+ days)</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Channels</label>
                    <div class="space-y-2">
                        <label class="flex items-center">
                            <input type="checkbox" name="channels[]" value="in_app" checked class="mr-2">
                            In-App
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="channels[]" value="email" class="mr-2">
                            Email
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="channels[]" value="sms" class="mr-2">
                            SMS
                        </label>
                    </div>
                </div>
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeTargetedModal()" class="px-4 py-2 text-gray-600 hover:text-gray-800">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">Send Targeted Notification</button>
                </div>
            </form>
        </div>
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
    
    // Keep only last 20 entries
    while (log.children.length > 20) {
        log.removeChild(log.lastChild);
    }
}

// Send individual test notification
async function sendTestNotification(type) {
    try {
        addToActivityLog(`Sending test ${type} notification...`);
        
        const response = await fetch(`/admin/notifications/test/${type}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                message: `Test ${type} notification sent from admin panel`
            })
        });
        
        const data = await response.json();
        
        if (response.ok) {
            addToActivityLog(`✓ Test ${type} notification sent successfully to ${data.target_user}`, 'success');
        } else {
            addToActivityLog(`✗ Failed to send test ${type} notification: ${data.error}`, 'error');
        }
    } catch (error) {
        addToActivityLog(`✗ Error sending test ${type} notification: ${error.message}`, 'error');
    }
    
    refreshStats();
}

// Clear test notifications
async function clearTestNotifications() {
    if (!confirm('Are you sure you want to clear all test notifications?')) {
        return;
    }
    
    try {
        addToActivityLog('Clearing test notifications...');
        
        const response = await fetch('/admin/notifications/test/clear', {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });
        
        const data = await response.json();
        
        if (response.ok) {
            addToActivityLog(`✓ Cleared ${data.deleted_count} test notifications`, 'success');
        } else {
            addToActivityLog(`✗ Failed to clear notifications: ${data.error}`, 'error');
        }
    } catch (error) {
        addToActivityLog(`✗ Error clearing notifications: ${error.message}`, 'error');
    }
    
    refreshStats();
}

// Bulk notification modal functions
function openBulkModal() {
    document.getElementById('bulk-modal').classList.remove('hidden');
}

function closeBulkModal() {
    document.getElementById('bulk-modal').classList.add('hidden');
}

function openTargetedModal() {
    document.getElementById('targeted-modal').classList.remove('hidden');
}

function closeTargetedModal() {
    document.getElementById('targeted-modal').classList.add('hidden');
}

// Handle bulk form submission
document.getElementById('bulk-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const data = Object.fromEntries(formData);
    
    // Handle channels array
    const channels = formData.getAll('channels[]');
    data.channels = channels;
    
    try {
        addToActivityLog('Sending bulk notification...');
        
        const response = await fetch('/admin/notifications/test/bulk', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (response.ok) {
            addToActivityLog(`✓ Bulk notification sent to ${result.user_type} users via ${result.channels.join(', ')}`, 'success');
            closeBulkModal();
            this.reset();
        } else {
            addToActivityLog(`✗ Failed to send bulk notification: ${result.error}`, 'error');
        }
    } catch (error) {
        addToActivityLog(`✗ Error sending bulk notification: ${error.message}`, 'error');
    }
    
    refreshStats();
});

// Handle targeted form submission
document.getElementById('targeted-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const data = Object.fromEntries(formData);
    
    // Handle channels array
    const channels = formData.getAll('channels[]');
    data.channels = channels;
    
    try {
        addToActivityLog('Sending targeted notification...');
        
        const response = await fetch('/admin/notifications/test/targeted', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (response.ok) {
            addToActivityLog(`✓ Targeted notification sent to ${result.target_group} via ${result.channels.join(', ')}`, 'success');
            closeTargetedModal();
            this.reset();
        } else {
            addToActivityLog(`✗ Failed to send targeted notification: ${result.error}`, 'error');
        }
    } catch (error) {
        addToActivityLog(`✗ Error sending targeted notification: ${error.message}`, 'error');
    }
    
    refreshStats();
});

// Refresh statistics
async function refreshStats() {
    try {
        const response = await fetch('/api/v1/notifications/statistics', {
            headers: {
                'Authorization': 'Bearer ' + localStorage.getItem('token'), // You may need to adjust this
                'Accept': 'application/json'
            }
        });
        
        if (response.ok) {
            const data = await response.json();
            document.getElementById('total-notifications').textContent = data.total_sent + data.total_scheduled + data.total_draft;
            document.getElementById('sent-notifications').textContent = data.total_sent;
            document.getElementById('test-notifications').textContent = data.sent_today; // Approximation
        }
    } catch (error) {
        console.error('Error refreshing stats:', error);
    }
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    refreshStats();
    addToActivityLog('Test notification interface loaded', 'info');
});
</script>
@endsection