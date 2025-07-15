# Package Lifecycle Management API Documentation

## Overview
The Package Lifecycle Management feature provides comprehensive tracking and management of customer packages, including automated notifications, renewal workflows, and freeze/unfreeze functionality.

## API Endpoints

### 1. List User Packages
```
GET /api/v1/user-packages
```

**Query Parameters:**
- `status` (optional): Filter by status (active, expiring_soon, expired, frozen)
- `user_id` (optional): Filter by specific user
- `expiring_soon` (optional): Boolean to filter packages expiring within 7 days

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "user_id": 1,
      "package_id": 1,
      "name": "Monthly Unlimited",
      "assigned_date": "2025-01-01",
      "expiry_date": "2025-02-01",
      "remaining_sessions": 20,
      "total_sessions": 30,
      "status": "active",
      "is_frozen": false,
      "auto_renew": true,
      "user": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com"
      },
      "package": {
        "id": 1,
        "name": "Monthly Unlimited",
        "price": 99.99
      }
    }
  ],
  "links": {...},
  "meta": {...}
}
```

### 2. Get Package Statistics
```
GET /api/v1/user-packages/statistics
```

**Response:**
```json
{
  "total_active": 150,
  "expiring_soon": 23,
  "expired": 45,
  "frozen": 8,
  "auto_renew_enabled": 67,
  "expiring_this_week": 15,
  "revenue_from_renewals": 4599.50
}
```

### 3. Get Expiring Packages Report
```
GET /api/v1/user-packages/expiring-report
```

**Response:**
```json
[
  {
    "id": 1,
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "phone": "+1234567890"
    },
    "package_name": "Monthly Unlimited",
    "days_until_expiry": 3,
    "expiry_date": "2025-01-15",
    "remaining_sessions": 5,
    "auto_renew": false,
    "last_notification": "2025-01-12 09:00:00"
  }
]
```

### 4. Get User's Packages
```
GET /api/v1/user-packages/user/{userId}
```

**Response:**
```json
[
  {
    "id": 1,
    "package_id": 1,
    "name": "Monthly Unlimited",
    "assigned_date": "2025-01-01",
    "expiry_date": "2025-02-01",
    "remaining_sessions": 20,
    "total_sessions": 30,
    "status": "active",
    "history": [...],
    "renewals": [...]
  }
]
```

### 5. Get Package Details
```
GET /api/v1/user-packages/{userPackageId}
```

**Response:**
```json
{
  "package": {
    "id": 1,
    "user_id": 1,
    "package_id": 1,
    "name": "Monthly Unlimited",
    "assigned_date": "2025-01-01",
    "expiry_date": "2025-02-01",
    "remaining_sessions": 20,
    "total_sessions": 30,
    "status": "active",
    "is_frozen": false,
    "frozen_at": null,
    "auto_renew": true,
    "user": {...},
    "package": {...},
    "history": [...],
    "notificationLogs": [...]
  },
  "stats": {
    "days_until_expiry": 24,
    "usage_percentage": 33.33,
    "can_be_used": true
  }
}
```

### 6. Assign Package to User
```
POST /api/v1/user-packages
```

**Request Body:**
```json
{
  "user_id": 1,
  "package_id": 3,
  "expiry_date": "2025-03-01", // optional, defaults to package duration
  "auto_renew": true
}
```

**Response:**
```json
{
  "message": "Package assigned successfully",
  "user_package": {
    "id": 5,
    "user_id": 1,
    "package_id": 3,
    "name": "Premium Monthly",
    "assigned_date": "2025-01-08",
    "expiry_date": "2025-03-01",
    "remaining_sessions": 50,
    "total_sessions": 50,
    "status": "active",
    "auto_renew": true
  }
}
```

### 7. Update Package Settings
```
PUT /api/v1/user-packages/{userPackageId}
```

**Request Body:**
```json
{
  "auto_renew": false,
  "expiry_date": "2025-03-15"
}
```

### 8. Freeze Package
```
POST /api/v1/user-packages/{userPackageId}/freeze
```

**Request Body:**
```json
{
  "duration_days": 30  // optional, max 90 days
}
```

**Response:**
```json
{
  "message": "Package frozen successfully",
  "user_package": {
    "id": 1,
    "status": "frozen",
    "is_frozen": true,
    "frozen_at": "2025-01-08 10:00:00",
    "freeze_duration_days": 30
  }
}
```

### 9. Unfreeze Package
```
POST /api/v1/user-packages/{userPackageId}/unfreeze
```

**Response:**
```json
{
  "message": "Package unfrozen successfully",
  "user_package": {
    "id": 1,
    "status": "active",
    "is_frozen": false,
    "unfrozen_at": "2025-01-15 10:00:00",
    "expiry_date": "2025-03-08"  // Extended based on freeze duration
  }
}
```

### 10. Renew Package
```
POST /api/v1/user-packages/{userPackageId}/renew
```

**Request Body:**
```json
{
  "package_id": 3,  // optional, defaults to same package
  "additional_sessions": 10  // optional
}
```

**Response:**
```json
{
  "message": "Package renewed successfully",
  "new_package": {
    "id": 6,
    "user_id": 1,
    "package_id": 3,
    "name": "Premium Monthly",
    "assigned_date": "2025-01-08",
    "expiry_date": "2025-02-08",
    "remaining_sessions": 60,
    "total_sessions": 60,
    "status": "active",
    "renewed_from_package_id": 1,
    "renewed_at": "2025-01-08 10:00:00"
  }
}
```

### 11. Send Manual Expiry Notification
```
POST /api/v1/user-packages/{userPackageId}/send-notification
```

**Response:**
```json
{
  "message": "Notification sent successfully"
}
```

## Automated Features

### 1. Package Status Updates
The system automatically updates package statuses based on:
- **Active**: Package is valid and has remaining sessions
- **Expiring Soon**: Package expires within 7 days
- **Expired**: Package expiry date has passed
- **Frozen**: Package is temporarily suspended

### 2. Automated Notifications
Notifications are sent automatically:
- **7 days before expiry**: First reminder
- **3 days before expiry**: Urgent reminder
- **On expiration**: Final notice
- **After renewal**: Confirmation

### 3. Auto-Renewal
Packages with `auto_renew` enabled will automatically renew 1 day before expiry.

### 4. Console Commands
Run daily via cron:
```bash
php artisan packages:check-expiry
```

This command:
- Updates all package statuses
- Sends expiry notifications
- Processes auto-renewals

## Admin Panel Features

### Package Management Dashboard
- View all user packages with filtering and search
- See statistics (active, expiring, expired, frozen)
- Quick actions (freeze, unfreeze, renew, notify)

### Expiring Packages Report
- List of all packages expiring within 7 days
- Contact information for users
- Bulk notification options

### Package Details View
- Complete package information
- Usage statistics and history
- Notification logs
- Action buttons for management

### Package Assignment
- Assign new packages to users
- Set custom expiry dates
- Enable/disable auto-renewal

### Package Renewal
- Renew with same or different package
- Add additional sessions
- Automatic expiry date calculation

## Database Schema

### user_packages table (updated)
- `is_frozen` (boolean)
- `frozen_at` (timestamp)
- `unfrozen_at` (timestamp)
- `freeze_duration_days` (integer)
- `last_notification_sent_at` (timestamp)
- `notification_stage` (string)
- `auto_renew` (boolean)
- `renewed_from_package_id` (foreign key)
- `renewed_at` (timestamp)
- `status` (enum: active, paused, expired, expiring_soon, frozen)

### package_history table
- `user_package_id` (foreign key)
- `user_id` (foreign key)
- `action` (string)
- `previous_status` (string)
- `new_status` (string)
- `sessions_before` (integer)
- `sessions_after` (integer)
- `expiry_date_before` (date)
- `expiry_date_after` (date)
- `notes` (json)
- `performed_by` (foreign key)

### package_notification_logs table
- `user_package_id` (foreign key)
- `user_id` (foreign key)
- `notification_type` (string)
- `channel` (string)
- `sent_successfully` (boolean)
- `error_message` (text)
- `days_until_expiry` (integer)

## Error Responses

All endpoints return standard error responses:

```json
{
  "message": "Package is already frozen",
  "errors": {
    "field": ["validation error message"]
  }
}
```

Common HTTP status codes:
- `200` - Success
- `201` - Created
- `400` - Bad Request
- `422` - Validation Error
- `404` - Not Found
- `500` - Server Error