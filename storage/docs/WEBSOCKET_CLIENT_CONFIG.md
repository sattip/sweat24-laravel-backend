# WebSocket Configuration για το Client App

## 1. Installation

Εγκατάστησε τα απαραίτητα packages:

```bash
npm install laravel-echo pusher-js
```

## 2. Configuration

Προσθέστε στο `.env` του Client App:

```env
VITE_REVERB_APP_KEY=sweat24appkey
VITE_REVERB_HOST=sweat93laravel.obs.com.gr
VITE_REVERB_PORT=443
VITE_REVERB_SCHEME=https
```

## 3. Echo Setup

Δημιούργησε ένα αρχείο `src/lib/echo.ts`:

```typescript
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

export const echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT || 443,
    forceTLS: true,
    enabledTransports: ['ws', 'wss'],
    authorizer: (channel, options) => {
        return {
            authorize: (socketId, callback) => {
                fetch('/api/v1/broadcasting/auth', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
                    },
                    body: JSON.stringify({
                        socket_id: socketId,
                        channel_name: channel.name,
                    })
                })
                .then(response => response.json())
                .then(data => callback(null, data))
                .catch(error => callback(error, null));
            }
        };
    }
});
```

## 4. Listening to Events

### 4.1 Order Ready for Pickup

```typescript
import { echo } from '@/lib/echo';

// Subscribe when user logs in
echo.private(`order.${userId}`)
    .listen('OrderReadyForPickup', (e) => {
        console.log('Order ready for pickup:', e.order);
        // Show push notification or update UI
        showNotification({
            title: 'Η παραγγελία σας είναι έτοιμη!',
            body: `Η παραγγελία #${e.order.order_number} είναι έτοιμη για παραλαβή.`,
            icon: '/notification-icon.png'
        });
    });
```

### 4.2 Sessions Ending Notification

```typescript
echo.private(`user.${userId}`)
    .listen('UserNearSessionsEnd', (e) => {
        const message = e.isLastSession
            ? 'Αυτή είναι η τελευταία σας συνεδρία!'
            : `Έχετε μόνο ${e.remainingSessions} συνεδρίες ακόμα!`;
        
        showNotification({
            title: 'Οι συνεδρίες σας τελειώνουν',
            body: message,
            icon: '/notification-icon.png'
        });
    });
```

### 4.3 Chat Message Received

```typescript
echo.private(`chat.${userId}`)
    .listen('ChatMessageReceived', (e) => {
        console.log('New chat message:', e.message);
        
        showNotification({
            title: 'Νέο μήνυμα',
            body: `${e.message.sender_name}: ${e.message.content}`,
            icon: '/notification-icon.png'
        });
        
        // Update chat UI
        updateChatMessages(e.message);
    });
```

### 4.4 New Event Created

```typescript
// Public channel - no authentication needed
echo.channel('events.all')
    .listen('EventCreated', (e) => {
        console.log('New event created:', e.event);
        
        showNotification({
            title: 'Νέο event!',
            body: `${e.event.title} - ${e.event.event_date}`,
            icon: '/notification-icon.png'
        });
        
        // Update events list
        refreshEventsList();
    });
```

### 4.5 Booking Request Status Changed

```typescript
echo.private(`booking-request.user.${userId}`)
    .listen('BookingRequestStatusChanged', (e) => {
        const statusMessage = e.newStatus === 'confirmed'
            ? `Το ραντεβού σας επιβεβαιώθηκε για ${e.bookingRequest.confirmed_date} στις ${e.bookingRequest.confirmed_time}`
            : `Το ραντεβού σας απορρίφθηκε. Λόγος: ${e.bookingRequest.rejection_reason}`;
        
        showNotification({
            title: e.newStatus === 'confirmed' ? 'Ραντεβού Επιβεβαιώθηκε!' : 'Ραντεβού Απορρίφθηκε',
            body: statusMessage,
            icon: '/notification-icon.png'
        });
    });
```

## 5. Helper Functions

```typescript
// Show browser notification
function showNotification(options: NotificationOptions & { title: string }) {
    if ('Notification' in window && Notification.permission === 'granted') {
        new Notification(options.title, options);
    } else if ('Notification' in window && Notification.permission !== 'denied') {
        Notification.requestPermission().then(permission => {
            if (permission === 'granted') {
                new Notification(options.title, options);
            }
        });
    }
    
    // Also show in-app notification
    // Your in-app notification logic here
}

// Cleanup on logout
function cleanupWebSocket() {
    echo.disconnect();
}
```

## 6. Notification History API Endpoint

**ΣΗΜΑΝΤΙΚΟ**: Το σωστό endpoint για το ιστορικό των notifications είναι:

```
GET /api/v1/notifications/user
```

Headers:
```
Authorization: Bearer {user_token}
```

Response:
```json
{
    "data": [
        {
            "id": "notification-uuid",
            "type": "App\\Notifications\\OrderReadyNotification",
            "notifiable_type": "App\\Models\\User",
            "notifiable_id": 1,
            "data": {
                "title": "Η παραγγελία σας είναι έτοιμη!",
                "message": "Η παραγγελία #ORD-2024-001 είναι έτοιμη για παραλαβή.",
                "order_id": 123,
                "order_number": "ORD-2024-001"
            },
            "read_at": null,
            "created_at": "2025-01-22T15:30:00.000000Z",
            "updated_at": "2025-01-22T15:30:00.000000Z"
        }
    ],
    "links": {
        "first": "...",
        "last": "...",
        "prev": null,
        "next": "..."
    },
    "meta": {
        "current_page": 1,
        "from": 1,
        "last_page": 5,
        "links": [...],
        "path": "...",
        "per_page": 15,
        "to": 15,
        "total": 73
    }
}
```

## 7. Mark Notifications as Read

```
POST /api/v1/notifications/{recipient}/read
```

Headers:
```
Authorization: Bearer {user_token}
Content-Type: application/json
```

Body:
```json
{
    "notification_id": "notification-uuid"
}
```

Response:
```json
{
    "message": "Notification marked as read",
    "data": {
        // Updated notification object
    }
}
```

## 8. Production Notes

Για production deployment:

1. Ο Reverb server πρέπει να τρέχει ως daemon/service
2. Χρήση supervisor ή systemd για auto-restart
3. Configure nginx για WebSocket proxy:

```nginx
location /app {
    proxy_pass http://localhost:8080;
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "upgrade";
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;
    proxy_read_timeout 60s;
    proxy_send_timeout 60s;
    proxy_connect_timeout 60s;
    proxy_buffering off;
}
```

4. SSL/TLS για secure WebSocket connections (wss://)

## 9. Testing

Για να δοκιμάσεις το σύστημα:

1. Login ως user
2. Άνοιξε browser console
3. Έλεγξε για WebSocket connection στο Network tab
4. Trigger ένα από τα events (π.χ. άλλαξε order status σε "ready_for_pickup")
5. Δες το notification να εμφανίζεται real-time 