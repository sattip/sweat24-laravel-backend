# SWEAT93 Mobile App - Waitlist Feature Integration Guide

## Overview

This document provides complete integration instructions for implementing the waitlist feature in the SWEAT93 mobile application. The waitlist system allows users to join a queue when a class is full and automatically notifies them when a spot becomes available.

---

## API Configuration

**Base URL**: `https://api.sweat93.gr/api/v1/`

**Authentication**: All waitlist endpoints require authentication. Include the bearer token in the Authorization header:

```typescript
headers: {
  'Authorization': `Bearer ${user_token}`,
  'Content-Type': 'application/json',
  'Accept': 'application/json'
}
```

---

## API Endpoints

### 1. Get User's Waitlists

**GET** `/my-waitlists`

Returns all active waitlist entries for the authenticated user.

**Response**:
```json
{
  "success": true,
  "data": [
    {
      "waitlist_id": 456,
      "position": 3,
      "status": "waiting",
      "notified_at": null,
      "expires_at": null,
      "created_at": "2025-11-17T10:30:00Z",
      "class": {
        "id": 123,
        "name": "Pilates Ενδιάμεσο",
        "date": "2025-11-20",
        "time": "18:00:00",
        "instructor": "Μαρία Παπαδοπούλου",
        "location": "Αίθουσα A",
        "available_spots": 0,
        "is_full": true
      }
    }
  ],
  "total": 1
}
```

**Status Values**:
- `waiting` - User is in queue
- `notified` - User has been notified of available spot (2-hour window to accept)

---

### 2. Join Waitlist

**POST** `/classes/{class_id}/waitlist/join`

Add the authenticated user to the waitlist for a specific class.

**Response (Success)**:
```json
{
  "success": true,
  "message": "Προστεθήκατε επιτυχώς στη λίστα αναμονής",
  "position": 3,
  "waitlist_id": 456
}
```

**Response (Errors)**:
```json
// Already in waitlist
{
  "success": false,
  "message": "Είστε ήδη στη λίστα αναμονής",
  "position": 3
}

// Already has confirmed booking
{
  "success": false,
  "message": "Έχετε ήδη κράτηση για αυτό το μάθημα"
}

// Class has available spots
{
  "success": false,
  "message": "Το μάθημα έχει διαθέσιμες θέσεις. Παρακαλώ κάντε κανονική κράτηση."
}
```

---

### 3. Check Waitlist Status

**GET** `/classes/{class_id}/waitlist/status`

Check the authenticated user's waitlist status for a specific class.

**Response (In Waitlist)**:
```json
{
  "in_waitlist": true,
  "position": 2,
  "status": "waiting",
  "notified_at": null,
  "expires_at": null
}
```

**Response (Notified)**:
```json
{
  "in_waitlist": true,
  "position": null,
  "status": "notified",
  "notified_at": "2025-11-17T12:00:00Z",
  "expires_at": "2025-11-17T14:00:00Z"
}
```

**Response (Not in Waitlist)**:
```json
{
  "in_waitlist": false
}
```

---

### 4. Leave Waitlist

**DELETE** `/classes/{class_id}/waitlist/leave`

Remove the authenticated user from the waitlist.

**Response**:
```json
{
  "success": true,
  "message": "Αφαιρεθήκατε από τη λίστα αναμονής"
}
```

---

### 5. Decline Waitlist Spot

**POST** `/classes/{class_id}/waitlist/decline`

Decline a waitlist spot notification. User can choose to leave entirely or move to back of queue.

**Request Body**:
```json
{
  "stay_in_waitlist": false  // true = move to back of queue, false = leave entirely
}
```

**Response**:
```json
{
  "success": true,
  "message": "Αφαιρεθήκατε από τη λίστα αναμονής",
  "stayed_in_waitlist": false
}
```

---

## Integration Flow

### User Journey: Booking a Full Class

```
1. User attempts to book a class
   └─> POST /bookings
       ├─> Success: Booking confirmed
       └─> Error 400: Class is full
           └─> Show "Join Waitlist" option

2. User joins waitlist
   └─> POST /classes/{id}/waitlist/join
       └─> Success: User added to position #3

3. User views "My Bookings" screen
   └─> GET /my-waitlists
       └─> Display waitlist entries with position

4. Another user cancels their booking
   └─> Backend automatically processes waitlist
       ├─> Moves position #1 to confirmed booking
       ├─> Sends email notification
       ├─> Sends push notification
       └─> In-app notification appears

5. User receives notification
   └─> Notification: "A spot opened up!"
       ├─> Deep link to booking details
       └─> Countdown timer: 2 hours to confirm

6. User opens app and sees notification
   └─> Booking already confirmed automatically
       └─> Show confirmation screen
```

---

## UI Implementation Recommendations

### 1. Class Details Screen (Full Class)

```typescript
interface ClassDetailsProps {
  classId: number;
  name: string;
  date: string;
  time: string;
  instructor: string;
  maxParticipants: number;
  currentParticipants: number;
  waitlistCount?: number;
}

// Component state
const [isInWaitlist, setIsInWaitlist] = useState(false);
const [waitlistPosition, setWaitlistPosition] = useState<number | null>(null);

// Check waitlist status on mount
useEffect(() => {
  checkWaitlistStatus(classId);
}, [classId]);

const checkWaitlistStatus = async (classId: number) => {
  const response = await api.get(`/classes/${classId}/waitlist/status`);
  setIsInWaitlist(response.in_waitlist);
  setWaitlistPosition(response.position);
};

// UI
return (
  <View>
    <Text style={styles.classTitle}>{name}</Text>
    <Text>{date} at {time}</Text>
    <Text>Instructor: {instructor}</Text>

    {/* Capacity indicator */}
    <View style={styles.capacityBar}>
      <Text>
        {currentParticipants}/{maxParticipants} participants
      </Text>
      <ProgressBar
        progress={currentParticipants / maxParticipants}
        color={currentParticipants >= maxParticipants ? 'red' : 'green'}
      />
    </View>

    {/* Booking / Waitlist actions */}
    {currentParticipants < maxParticipants ? (
      <Button
        title="Book Now"
        onPress={() => bookClass(classId)}
      />
    ) : isInWaitlist ? (
      <View style={styles.waitlistStatus}>
        <Icon name="clock" size={20} color="#FFA500" />
        <Text>You're on the waitlist</Text>
        <Text style={styles.position}>Position #{waitlistPosition}</Text>
        <Button
          title="Leave Waitlist"
          variant="outline"
          onPress={() => leaveWaitlist(classId)}
        />
      </View>
    ) : (
      <View>
        <View style={styles.fullBanner}>
          <Icon name="users" size={20} />
          <Text>Class is Full</Text>
        </View>
        {waitlistCount !== undefined && (
          <Text style={styles.waitlistInfo}>
            {waitlistCount} {waitlistCount === 1 ? 'person' : 'people'} in waitlist
          </Text>
        )}
        <Button
          title="Join Waitlist"
          variant="secondary"
          onPress={() => joinWaitlist(classId)}
        />
      </View>
    )}
  </View>
);
```

---

### 2. My Bookings Screen

```typescript
interface BookingCardProps {
  booking: Booking;
  waitlistStatus?: WaitlistEntry;
}

const BookingCard: React.FC<BookingCardProps> = ({ booking, waitlistStatus }) => {
  return (
    <Card>
      <CardHeader>
        <Text style={styles.className}>{booking.class_name}</Text>
        {waitlistStatus && (
          <Badge
            variant={waitlistStatus.status === 'notified' ? 'warning' : 'info'}
          >
            {waitlistStatus.status === 'notified'
              ? `🔔 Spot Available!`
              : `Waitlist #${waitlistStatus.position}`
            }
          </Badge>
        )}
      </CardHeader>

      <CardBody>
        <Text>📅 {booking.date}</Text>
        <Text>⏰ {booking.time}</Text>
        <Text>👤 {booking.instructor}</Text>

        {waitlistStatus?.status === 'notified' && (
          <View style={styles.notificationBanner}>
            <Text style={styles.urgent}>A spot opened up!</Text>
            <CountdownTimer expiresAt={waitlistStatus.expires_at} />
            <Text style={styles.expiresInfo}>
              Your spot is reserved for 2 hours
            </Text>
            <View style={styles.actions}>
              <Button
                title="View Booking"
                onPress={() => navigate('BookingDetails', { id: booking.id })}
              />
            </View>
          </View>
        )}
      </CardBody>
    </Card>
  );
};
```

---

### 3. Countdown Timer Component

```typescript
interface CountdownTimerProps {
  expiresAt: string;
}

const CountdownTimer: React.FC<CountdownTimerProps> = ({ expiresAt }) => {
  const [timeRemaining, setTimeRemaining] = useState('');

  useEffect(() => {
    const interval = setInterval(() => {
      const now = new Date().getTime();
      const expiry = new Date(expiresAt).getTime();
      const difference = expiry - now;

      if (difference > 0) {
        const hours = Math.floor(difference / (1000 * 60 * 60));
        const minutes = Math.floor((difference % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((difference % (1000 * 60)) / 1000);

        setTimeRemaining(`${hours}h ${minutes}m ${seconds}s`);
      } else {
        setTimeRemaining('Expired');
        clearInterval(interval);
      }
    }, 1000);

    return () => clearInterval(interval);
  }, [expiresAt]);

  return (
    <View style={styles.timer}>
      <Icon name="clock" />
      <Text style={styles.timerText}>Expires in: {timeRemaining}</Text>
    </View>
  );
};
```

---

## Push Notifications

### Notification Payload Structure

When a waitlist spot becomes available, the backend sends a push notification with the following payload:

```json
{
  "notification": {
    "title": "Διαθέσιμη θέση στο μάθημα!",
    "body": "Μια θέση ελευθερώθηκε στο μάθημα Pilates Ενδιάμεσο"
  },
  "data": {
    "type": "waitlist_spot_available",
    "class_id": "123",
    "class_name": "Pilates Ενδιάμεσο",
    "booking_id": "789",
    "expires_at": "2025-11-17T14:00:00Z",
    "action_url": "/bookings/789"
  }
}
```

### Handling Notifications

```typescript
// React Native Firebase example
import messaging from '@react-native-firebase/messaging';
import { useNavigation } from '@react-navigation/native';

// Background/Quit state
messaging().setBackgroundMessageHandler(async remoteMessage => {
  console.log('Background notification:', remoteMessage);

  if (remoteMessage.data?.type === 'waitlist_spot_available') {
    // Store notification locally to show when app opens
    await AsyncStorage.setItem(
      'pending_waitlist_notification',
      JSON.stringify(remoteMessage.data)
    );
  }
});

// Foreground state
useEffect(() => {
  const unsubscribe = messaging().onMessage(async remoteMessage => {
    if (remoteMessage.data?.type === 'waitlist_spot_available') {
      // Show in-app alert
      Alert.alert(
        remoteMessage.notification?.title || 'Spot Available!',
        remoteMessage.notification?.body || 'A spot opened up in your waitlisted class',
        [
          {
            text: 'View',
            onPress: () => navigation.navigate('BookingDetails', {
              id: remoteMessage.data.booking_id
            })
          },
          { text: 'Later', style: 'cancel' }
        ]
      );

      // Refresh waitlist data
      await refreshWaitlists();
    }
  });

  return unsubscribe;
}, []);

// Notification tap (app in background/quit)
messaging().onNotificationOpenedApp(remoteMessage => {
  if (remoteMessage.data?.type === 'waitlist_spot_available') {
    navigation.navigate('BookingDetails', {
      id: remoteMessage.data.booking_id
    });
  }
});

// Initial notification (app opened from quit via notification)
messaging()
  .getInitialNotification()
  .then(remoteMessage => {
    if (remoteMessage?.data?.type === 'waitlist_spot_available') {
      navigation.navigate('BookingDetails', {
        id: remoteMessage.data.booking_id
      });
    }
  });
```

---

## API Service Implementation

```typescript
// api/waitlist.ts
import { apiClient } from './client';

export interface WaitlistEntry {
  waitlist_id: number;
  position: number | null;
  status: 'waiting' | 'notified';
  notified_at: string | null;
  expires_at: string | null;
  created_at: string;
  class: {
    id: number;
    name: string;
    date: string;
    time: string;
    instructor: string;
    location: string;
    available_spots: number;
    is_full: boolean;
  };
}

export interface WaitlistStatus {
  in_waitlist: boolean;
  position?: number | null;
  status?: 'waiting' | 'notified';
  notified_at?: string | null;
  expires_at?: string | null;
}

export const waitlistApi = {
  // Get all user's waitlists
  getMyWaitlists: async (): Promise<WaitlistEntry[]> => {
    const response = await apiClient.get('/my-waitlists');
    return response.data.data;
  },

  // Check status for specific class
  checkStatus: async (classId: number): Promise<WaitlistStatus> => {
    const response = await apiClient.get(`/classes/${classId}/waitlist/status`);
    return response.data;
  },

  // Join waitlist
  join: async (classId: number): Promise<{ position: number; waitlist_id: number }> => {
    const response = await apiClient.post(`/classes/${classId}/waitlist/join`);
    return response.data;
  },

  // Leave waitlist
  leave: async (classId: number): Promise<void> => {
    await apiClient.delete(`/classes/${classId}/waitlist/leave`);
  },

  // Decline spot (with option to stay)
  decline: async (classId: number, stayInWaitlist: boolean = false): Promise<void> => {
    await apiClient.post(`/classes/${classId}/waitlist/decline`, {
      stay_in_waitlist: stayInWaitlist
    });
  },
};
```

---

## Error Handling

```typescript
const handleWaitlistAction = async (
  action: () => Promise<any>,
  successMessage: string
) => {
  try {
    setLoading(true);
    await action();
    showToast(successMessage, 'success');
  } catch (error) {
    if (axios.isAxiosError(error)) {
      const message = error.response?.data?.message || 'An error occurred';
      showToast(message, 'error');
    } else {
      showToast('Network error. Please try again.', 'error');
    }
  } finally {
    setLoading(false);
  }
};

// Usage
await handleWaitlistAction(
  () => waitlistApi.join(classId),
  'Successfully joined waitlist!'
);
```

---

## Testing Scenarios

### Scenario 1: Full Class Flow
1. Create a class with max_participants = 5
2. Book 5 spots (class is now full)
3. Attempt to book 6th spot → should fail with "Class is full"
4. Join waitlist → should get position 1
5. Cancel one of the 5 bookings
6. Position 1 user should receive notification
7. Their booking should auto-confirm
8. Verify countdown timer shows 2 hours

### Scenario 2: Multiple Waitlist Users
1. Full class with 3 users in waitlist
2. Cancel one booking
3. Position 1 should be notified
4. Positions 2 and 3 should move up
5. Position 1 declines (stays in waitlist)
6. Should move to position 3
7. Position 2 (now 1) should be notified

### Scenario 3: Notification Expiration
1. User gets notified of available spot
2. Don't respond for 2 hours
3. Spot should expire
4. Next person should be notified

---

## Best Practices

1. **Real-time Updates**: Poll `/my-waitlists` endpoint every 30 seconds when on bookings screen
2. **Cache Management**: Clear waitlist cache after joining/leaving
3. **Deep Linking**: Implement deep links for notification taps
4. **Offline Support**: Queue waitlist actions when offline, sync when online
5. **User Feedback**: Always show loading states and success/error messages
6. **Accessibility**: Ensure countdown timers have screen reader support
7. **Analytics**: Track waitlist join/leave/decline events

---

## Support & Troubleshooting

**Common Issues**:

1. **"Already in waitlist" error when joining**
   - Check if user has existing waitlist entry
   - Clear local cache and refresh

2. **Notification not received**
   - Verify FCM token is registered
   - Check notification permissions
   - Verify backend logs for notification dispatch

3. **Countdown timer incorrect**
   - Ensure device time is synced
   - Use server time from API response

**Contact**: For API issues, check Laravel logs at `/home/forge/api.sweat93.gr/storage/logs/laravel.log`

---

## Changelog

- **2025-11-17**: Initial waitlist feature implementation
  - Added expiration handling
  - Added decline endpoint with stay option
  - Added my-waitlists endpoint
  - Configured database queue for notifications
