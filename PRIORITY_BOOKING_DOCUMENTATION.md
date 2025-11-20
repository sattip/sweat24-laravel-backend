# Priority Booking System Documentation

## Overview
The Priority Booking System allows certain users to book gym classes earlier than regular users, with reserved priority seats that are automatically released to all users 24 hours before the class starts.

## Features

### 1. Priority Users
- Users can be granted priority booking access
- Priority can be time-limited (expires_at) or permanent
- Users can configure how many hours in advance they can book (default: 48 hours)

### 2. Priority Seats
- Each class can have a specified number of priority seats
- Priority seats are only bookable by priority users
- Unused priority seats are automatically released 24 hours before class

### 3. Automatic Package Integration
- Specific packages can grant automatic priority access
- Users with these packages automatically get priority status
- Priority expires when the package expires

## Database Schema

### Users Table
- `has_priority_booking` (boolean) - Whether user has priority access
- `priority_booking_expires_at` (datetime) - When priority access expires
- `priority_booking_hours_advance` (integer) - How many hours in advance user can book

### Gym Classes Table
- `priority_seats` (integer) - Number of reserved priority seats
- `priority_seats_booked` (integer) - Number of booked priority seats
- `priority_seats_release_at` (datetime) - When to release unused priority seats
- `priority_booking_enabled` (boolean) - Whether priority booking is enabled for this class

### Bookings Table
- `is_priority_booking` (boolean) - Whether this booking used a priority seat

### Priority Booking Settings Table
- `default_priority_seats` - Default number of priority seats for new classes
- `priority_advance_hours` - Default hours in advance priority users can book
- `priority_release_hours` - Hours before class to release unused priority seats
- `auto_release_enabled` - Whether automatic release is enabled
- `priority_system_enabled` - Whether the entire system is active
- `priority_packages` - Array of package IDs that grant priority access

## API Endpoints

### Admin Endpoints (Requires admin role)

#### Settings Management
```http
GET /api/v1/priority-booking/settings
PUT /api/v1/priority-booking/settings
```

Update settings example:
```json
{
  "default_priority_seats": 5,
  "priority_advance_hours": 48,
  "priority_release_hours": 24,
  "auto_release_enabled": true,
  "priority_system_enabled": true,
  "priority_packages": [1, 2, 3]
}
```

#### User Priority Management
```http
POST /api/v1/priority-booking/users/{userId}/grant
DELETE /api/v1/priority-booking/users/{userId}/revoke
GET /api/v1/priority-booking/users
```

Grant priority example:
```json
{
  "expires_at": "2025-12-31 23:59:59",
  "hours_advance": 72
}
```

#### Class Priority Management
```http
PUT /api/v1/priority-booking/classes/{classId}/priority
POST /api/v1/priority-booking/classes/{classId}/release
```

Update class priority example:
```json
{
  "priority_seats": 8,
  "priority_booking_enabled": true
}
```

#### Statistics
```http
GET /api/v1/priority-booking/stats
POST /api/v1/priority-booking/sync-packages
```

### User Endpoints

#### Check Class Availability
```http
GET /api/v1/priority-booking/classes/{classId}/availability
```

Response example:
```json
{
  "success": true,
  "data": {
    "total_capacity": 20,
    "current_participants": 10,
    "priority_seats": 5,
    "priority_seats_booked": 2,
    "priority_seats_available": 3,
    "regular_seats_available": 7,
    "total_available": 10,
    "priority_enabled": true,
    "priority_released": false,
    "user_can_book": true,
    "user_has_priority": true,
    "booking_as_priority": true,
    "hours_until_class": 36,
    "priority_window_active": true,
    "priority_release_time": "2025-10-16 10:00:00"
  }
}
```

## Booking Logic

### Priority User Booking Rules
1. Must have active priority status
2. Class must be within their advance booking window (e.g., 48 hours)
3. Priority seats must be available
4. After priority release (24h before), can book any available seat

### Regular User Booking Rules
1. Can only book regular (non-priority) seats
2. Cannot book priority seats until release time
3. After priority release (24h before), can book any available seat

### Automatic Priority Seat Release
- Runs every 30 minutes via scheduled job
- Releases unused priority seats 24 hours before class
- Released seats become available to all users

## Usage Examples

### 1. Grant Priority to User
```php
use App\Services\PriorityBookingService;

$service = new PriorityBookingService();
$user = User::find(1);

// Grant permanent priority with 72 hour advance booking
$service->grantPriorityAccess($user, null, 72);

// Grant temporary priority until end of year
$expiresAt = Carbon::parse('2025-12-31 23:59:59');
$service->grantPriorityAccess($user, $expiresAt, 48);
```

### 2. Create Booking with Priority
```php
$service = new PriorityBookingService();
$user = User::find(1);
$class = GymClass::find(10);

try {
    $booking = $service->createBooking($user, $class);
    
    if ($booking->is_priority_booking) {
        echo "Booked with priority seat!";
    }
} catch (\Exception $e) {
    echo "Booking failed: " . $e->getMessage();
}
```

### 3. Check User's Booking Ability
```php
$service = new PriorityBookingService();
$canBook = $service->canUserBookClass($user, $class);

if ($canBook['can_book']) {
    echo "User can book!";
    if ($canBook['is_priority']) {
        echo " Using priority seat.";
    }
} else {
    echo "Cannot book: " . $canBook['reason'];
}
```

### 4. Configure Class Priority Seats
```php
$class = GymClass::find(1);
$class->update([
    'priority_seats' => 5,
    'priority_booking_enabled' => true
]);

// Calculate automatic release time
$classDateTime = Carbon::parse($class->date . ' ' . $class->time);
$releaseTime = $classDateTime->subHours(24);
$class->update(['priority_seats_release_at' => $releaseTime]);
```

## Scheduled Jobs

### Release Priority Seats Command
Runs every 30 minutes to release unused priority seats:

```bash
php artisan priority:release-seats
```

This is automatically scheduled in `app/Console/Kernel.php`.

## Testing

### Test User Priority Access
```bash
# Grant priority to user
curl -X POST "https://api.sweat93.gr/api/v1/priority-booking/users/1/grant" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "expires_at": "2025-12-31 23:59:59",
    "hours_advance": 48
  }'

# Check class availability
curl -X GET "https://api.sweat93.gr/api/v1/priority-booking/classes/1/availability" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Test Priority Settings
```bash
# Get current settings
curl -X GET "https://api.sweat93.gr/api/v1/priority-booking/settings" \
  -H "Authorization: Bearer YOUR_TOKEN"

# Update settings
curl -X PUT "https://api.sweat93.gr/api/v1/priority-booking/settings" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "default_priority_seats": 5,
    "priority_release_hours": 24
  }'
```

## Configuration Examples

### Scenario 1: VIP Members Package
1. Create a VIP package in the system
2. Add package ID to priority settings
3. All users with active VIP package automatically get priority

### Scenario 2: Limited Time Promotion
1. Grant priority to specific users with expiration date
2. Priority automatically expires after the date

### Scenario 3: Different Class Types
1. Set different priority seat counts per class
2. Popular classes: more priority seats
3. Less popular classes: fewer or no priority seats

## Troubleshooting

### Priority Seats Not Releasing
1. Check if `auto_release_enabled` is true in settings
2. Verify the scheduled job is running: `php artisan schedule:list`
3. Check logs for errors in release job

### User Can't Book Despite Priority
1. Verify user has active priority: `has_priority_booking = true`
2. Check if priority has expired: `priority_booking_expires_at`
3. Ensure class is within advance booking window
4. Verify priority seats are available

### Package Priority Not Working
1. Ensure package ID is in `priority_packages` setting
2. Run sync command: `php artisan priority:sync-packages`
3. Check if user's package is active and not expired

## Best Practices

1. **Set Reasonable Priority Seats**: Don't reserve too many priority seats that might go unused
2. **Monitor Usage**: Regularly check stats to optimize priority seat allocation
3. **Communicate Changes**: Notify users when their priority status changes
4. **Test Release Timing**: Ensure 24-hour release window works for your schedule
5. **Package Integration**: Keep priority packages list updated

## Security Considerations

1. Only admins can modify priority settings and grant priority access
2. Users can only view availability for classes they can potentially book
3. Priority status is validated on every booking attempt
4. All priority changes are logged for audit purposes

---

**Version**: 1.0
**Created**: October 15, 2025
**Status**: Production Ready