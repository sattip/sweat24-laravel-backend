/**
 * Debug Panel for Client-Side Notification Testing
 * Only available in development mode
 */
class NotificationDebugPanel {
    constructor() {
        this.apiBase = '/api/v1/debug';
        this.isVisible = false;
        this.init();
    }

    init() {
        // Only create in development mode
        if (this.getEnvironment() !== 'local') {
            return;
        }

        this.createDebugPanel();
        this.bindEvents();
        this.loadInitialState();
    }

    getEnvironment() {
        // This would need to be set by the backend
        return window.APP_ENV || 'production';
    }

    createDebugPanel() {
        const panel = document.createElement('div');
        panel.id = 'debug-panel';
        panel.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            width: 300px;
            background: #1f2937;
            color: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            z-index: 10000;
            font-family: monospace;
            font-size: 12px;
            display: none;
        `;

        panel.innerHTML = `
            <div id="debug-header" style="background: #374151; padding: 10px; border-radius: 8px 8px 0 0; cursor: pointer; display: flex; justify-content: between; align-items: center;">
                <span>🔧 Debug Panel</span>
                <button id="debug-close" style="background: #ef4444; color: white; border: none; border-radius: 4px; padding: 2px 8px; cursor: pointer;">×</button>
            </div>
            <div id="debug-content" style="padding: 15px;">
                <div style="margin-bottom: 15px;">
                    <strong>Notification Bell State:</strong>
                    <div id="bell-state" style="margin-left: 10px; color: #9ca3af;">Loading...</div>
                </div>
                
                <div style="margin-bottom: 15px;">
                    <strong>Quick Actions:</strong>
                    <div style="margin-top: 5px;">
                        <button id="simulate-notification" style="background: #3b82f6; color: white; border: none; border-radius: 4px; padding: 5px 10px; margin: 2px; cursor: pointer;">Simulate Notification</button>
                        <button id="clear-notifications" style="background: #f59e0b; color: white; border: none; border-radius: 4px; padding: 5px 10px; margin: 2px; cursor: pointer;">Clear All</button>
                    </div>
                    <div style="margin-top: 5px;">
                        <button id="mark-all-read" style="background: #10b981; color: white; border: none; border-radius: 4px; padding: 5px 10px; margin: 2px; cursor: pointer;">Mark All Read</button>
                        <button id="mark-all-unread" style="background: #8b5cf6; color: white; border: none; border-radius: 4px; padding: 5px 10px; margin: 2px; cursor: pointer;">Mark All Unread</button>
                    </div>
                </div>
                
                <div style="margin-bottom: 15px;">
                    <strong>System Status:</strong>
                    <div id="system-status" style="margin-left: 10px; color: #9ca3af;">Loading...</div>
                </div>
                
                <div>
                    <strong>Activity Log:</strong>
                    <div id="debug-log" style="background: #111827; padding: 8px; border-radius: 4px; max-height: 120px; overflow-y: auto; margin-top: 5px; font-size: 10px;"></div>
                </div>
            </div>
        `;

        document.body.appendChild(panel);

        // Create toggle button
        const toggleBtn = document.createElement('button');
        toggleBtn.id = 'debug-toggle';
        toggleBtn.innerHTML = '🔧';
        toggleBtn.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: #1f2937;
            color: white;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            cursor: pointer;
            z-index: 10001;
            font-size: 16px;
        `;
        document.body.appendChild(toggleBtn);
    }

    bindEvents() {
        const panel = document.getElementById('debug-panel');
        const toggleBtn = document.getElementById('debug-toggle');

        if (!panel || !toggleBtn) return;

        // Toggle panel visibility
        toggleBtn.addEventListener('click', () => {
            this.isVisible = !this.isVisible;
            panel.style.display = this.isVisible ? 'block' : 'none';
            toggleBtn.style.display = this.isVisible ? 'none' : 'block';
        });

        // Close panel
        document.getElementById('debug-close').addEventListener('click', () => {
            this.isVisible = false;
            panel.style.display = 'none';
            toggleBtn.style.display = 'block';
        });

        // Debug actions
        document.getElementById('simulate-notification').addEventListener('click', () => {
            this.simulateNotification();
        });

        document.getElementById('clear-notifications').addEventListener('click', () => {
            this.clearAllNotifications();
        });

        document.getElementById('mark-all-read').addEventListener('click', () => {
            this.markAllAsRead();
        });

        document.getElementById('mark-all-unread').addEventListener('click', () => {
            this.markAllAsUnread();
        });

        // Auto-refresh every 30 seconds
        setInterval(() => {
            if (this.isVisible) {
                this.loadInitialState();
            }
        }, 30000);
    }

    async loadInitialState() {
        try {
            await this.updateBellState();
            await this.updateSystemStatus();
        } catch (error) {
            this.log('Error loading initial state: ' + error.message, 'error');
        }
    }

    async updateBellState() {
        try {
            const response = await this.apiCall('GET', '/notifications/bell-state');
            const bellState = response.bell_state;
            
            const bellStateEl = document.getElementById('bell-state');
            if (bellStateEl) {
                bellStateEl.innerHTML = `
                    <div>Unread: ${bellState.unread_count}</div>
                    <div>Total: ${bellState.total_count}</div>
                    <div>Has Unread: ${bellState.has_unread ? 'Yes' : 'No'}</div>
                `;
            }
        } catch (error) {
            this.log('Error updating bell state: ' + error.message, 'error');
        }
    }

    async updateSystemStatus() {
        try {
            const response = await this.apiCall('GET', '/system/status');
            const status = response.status;
            
            const statusEl = document.getElementById('system-status');
            if (statusEl) {
                statusEl.innerHTML = `
                    <div>Env: ${status.environment}</div>
                    <div>User: ${status.current_user.name}</div>
                    <div>Type: ${status.current_user.membership_type}</div>
                `;
            }
        } catch (error) {
            this.log('Error updating system status: ' + error.message, 'error');
        }
    }

    async simulateNotification() {
        try {
            const response = await this.apiCall('POST', '/notifications/simulate-receive', {
                title: 'Debug Test Notification',
                message: 'This is a test notification from the debug panel.',
                type: 'info'
            });
            
            this.log('✓ Notification simulated successfully', 'success');
            await this.updateBellState();
        } catch (error) {
            this.log('✗ Error simulating notification: ' + error.message, 'error');
        }
    }

    async clearAllNotifications() {
        try {
            const response = await this.apiCall('DELETE', '/notifications/clear-all');
            
            this.log(`✓ Cleared ${response.deleted_count} notifications`, 'success');
            await this.updateBellState();
        } catch (error) {
            this.log('✗ Error clearing notifications: ' + error.message, 'error');
        }
    }

    async markAllAsRead() {
        try {
            const response = await this.apiCall('PUT', '/notifications/mark-all-read');
            
            this.log(`✓ Marked ${response.updated_count} notifications as read`, 'success');
            await this.updateBellState();
        } catch (error) {
            this.log('✗ Error marking notifications as read: ' + error.message, 'error');
        }
    }

    async markAllAsUnread() {
        try {
            const response = await this.apiCall('PUT', '/notifications/mark-all-unread');
            
            this.log(`✓ Marked ${response.updated_count} notifications as unread`, 'success');
            await this.updateBellState();
        } catch (error) {
            this.log('✗ Error marking notifications as unread: ' + error.message, 'error');
        }
    }

    async apiCall(method, endpoint, data = null) {
        const token = this.getAuthToken();
        
        const options = {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'Authorization': `Bearer ${token}`
            }
        };

        if (data) {
            options.body = JSON.stringify(data);
        }

        const response = await fetch(this.apiBase + endpoint, options);
        
        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.error || 'API request failed');
        }

        return await response.json();
    }

    getAuthToken() {
        // This would depend on how authentication is implemented
        // Common locations: localStorage, sessionStorage, or a meta tag
        return localStorage.getItem('auth_token') || 
               sessionStorage.getItem('auth_token') || 
               document.querySelector('meta[name="api-token"]')?.getAttribute('content') || 
               '';
    }

    log(message, type = 'info') {
        const logEl = document.getElementById('debug-log');
        if (!logEl) return;

        const timestamp = new Date().toLocaleTimeString();
        const color = type === 'error' ? '#ef4444' : type === 'success' ? '#10b981' : '#60a5fa';
        
        const logEntry = document.createElement('div');
        logEntry.style.color = color;
        logEntry.innerHTML = `${timestamp} - ${message}`;
        
        logEl.insertBefore(logEntry, logEl.firstChild);
        
        // Keep only last 20 entries
        while (logEl.children.length > 20) {
            logEl.removeChild(logEl.lastChild);
        }
    }
}

// Initialize debug panel when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Only initialize in development mode
    if (window.APP_ENV === 'local') {
        window.debugPanel = new NotificationDebugPanel();
    }
});

// Usage instructions (remove in production)
console.log(`
🔧 Debug Panel Available!
=========================
The notification debug panel is active in development mode.
- Click the 🔧 button in the top-right corner to open/close the panel
- Use the buttons to simulate notifications and test functionality
- The panel auto-refreshes every 30 seconds

Make sure to set window.APP_ENV = 'local' in your application for the panel to appear.
`);