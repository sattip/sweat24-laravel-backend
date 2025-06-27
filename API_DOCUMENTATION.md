# SWEAT24 Backend API Documentation

Complete API documentation for the SWEAT24 Gym Management System.

## Base URL

- **Development**: `http://localhost:8001/api/v1`
- **Production**: `https://your-api-domain.com/api/v1`

## Authentication

The API uses Laravel Sanctum for authentication. Include the token in the Authorization header:

```http
Authorization: Bearer {your-token}
```

## Response Format

All API responses follow this format:

```json
{
  "data": {},
  "message": "Success message",
  "status": "success|error"
}
```

Error responses:

```json
{
  "message": "Error message",
  "errors": {
    "field": ["Validation error"]
  }
}
```

## Authentication Endpoints

### POST /auth/login

Login user and receive authentication token.

**Request:**
```json
{
  "email": "user@example.com",
  "password": "password"
}
```

**Response:**
```json
{
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "user@example.com",
    "created_at": "2023-01-01T00:00:00.000000Z"
  },
  "token": "1|abc123..."
}
```

### POST /auth/register

Register a new user.

**Request:**
```json
{
  "name": "John Doe",
  "email": "user@example.com",
  "password": "password",
  "password_confirmation": "password",
  "phone": "+30123456789",
  "date_of_birth": "1990-01-01"
}
```

### POST /auth/logout

Logout current user (requires authentication).

### GET /auth/me

Get current authenticated user information.

## User Management

### GET /users

Get all users (paginated).

**Query Parameters:**
- `page`: Page number
- `per_page`: Items per page (max 100)
- `search`: Search by name or email
- `is_active`: Filter by active status

### POST /users

Create a new user.

**Request:**
```json
{
  "name": "Jane Doe",
  "email": "jane@example.com",
  "password": "password",
  "phone": "+30123456789",
  "date_of_birth": "1985-05-15",
  "emergency_contact": "John Doe",
  "emergency_phone": "+30987654321",
  "is_active": true
}
```

### GET /users/{id}

Get specific user by ID.

### PUT /users/{id}

Update user information.

### DELETE /users/{id}

Delete user (soft delete).

## Instructors Management

### GET /instructors

Get all instructors.

**Query Parameters:**
- `specialty`: Filter by specialty
- `experience_min`: Minimum years of experience
- `available`: Filter by availability

**Response:**
```json
[
  {
    "id": 1,
    "name": "Alex Rodriguez",
    "email": "alex@sweat24.com",
    "phone": "+30123456789",
    "specialties": ["Strength Training", "Weight Loss"],
    "experience_years": 10,
    "hourly_rate": 50.00,
    "commission_rate": 15.00,
    "is_active": true
  }
]
```

### POST /instructors

Create new instructor.

### GET /instructors/{id}

Get specific instructor.

### PUT /instructors/{id}

Update instructor information.

## Classes Management

### GET /classes

Get all gym classes.

**Query Parameters:**
- `date`: Filter by date (YYYY-MM-DD)
- `instructor_id`: Filter by instructor
- `status`: Filter by status (scheduled, completed, cancelled)

**Response:**
```json
[
  {
    "id": 1,
    "name": "Morning HIIT",
    "description": "High-intensity interval training",
    "instructor_id": 1,
    "instructor_name": "Alex Rodriguez",
    "capacity": 20,
    "current_bookings": 15,
    "duration": 45,
    "price": 25.00,
    "date": "2023-12-01",
    "time": "09:00:00",
    "status": "scheduled"
  }
]
```

### POST /classes

Create new class.

**Request:**
```json
{
  "name": "Evening Yoga",
  "description": "Relaxing yoga session",
  "instructor_id": 2,
  "capacity": 15,
  "duration": 60,
  "price": 20.00,
  "date": "2023-12-02",
  "time": "19:00:00"
}
```

### GET /classes/{id}

Get specific class details.

### PUT /classes/{id}

Update class information.

### DELETE /classes/{id}

Cancel class.

## Bookings Management

### GET /bookings

Get user's bookings (authenticated user) or all bookings (admin).

**Query Parameters:**
- `status`: Filter by status
- `date_from`: Start date filter
- `date_to`: End date filter

**Response:**
```json
[
  {
    "id": 1,
    "user_id": 1,
    "class_id": 1,
    "class_name": "Morning HIIT",
    "instructor_name": "Alex Rodriguez",
    "booking_date": "2023-12-01",
    "booking_time": "09:00:00",
    "status": "confirmed",
    "checked_in": false,
    "notes": "First time booking",
    "created_at": "2023-11-30T10:00:00.000000Z"
  }
]
```

### POST /bookings

Create new booking.

**Request:**
```json
{
  "class_id": 1,
  "notes": "Looking forward to the class"
}
```

### PUT /bookings/{id}

Update booking (change status, add notes).

**Request:**
```json
{
  "status": "cancelled",
  "notes": "Emergency came up"
}
```

### POST /bookings/{id}/check-in

Check in to a class.

### DELETE /bookings/{id}

Cancel booking.

## Packages Management

### GET /packages

Get all membership packages.

**Query Parameters:**
- `active`: Filter by active status

**Response:**
```json
[
  {
    "id": 1,
    "name": "Basic Monthly",
    "description": "Access to all group classes",
    "price": 49.99,
    "duration_days": 30,
    "class_credits": 8,
    "features": ["Group Classes", "Locker Access"],
    "is_active": true
  }
]
```

### GET /user/packages

Get current user's packages.

### POST /user/packages

Purchase a package.

**Request:**
```json
{
  "package_id": 1,
  "payment_method": "card",
  "auto_renew": false
}
```

## Financial Management

### GET /payment-installments

Get payment installments.

**Query Parameters:**
- `status`: Filter by status (pending, paid, overdue)
- `customer`: Search by customer name

### POST /payment-installments

Create payment installment.

### GET /cash-register

Get cash register entries.

**Query Parameters:**
- `type`: Filter by type (income, withdrawal)
- `date_from`: Start date
- `date_to`: End date

### POST /cash-register

Add cash register entry.

### GET /business-expenses

Get business expenses.

**Query Parameters:**
- `category`: Filter by category
- `approved`: Filter by approval status
- `date_from`: Start date
- `date_to`: End date

### POST /business-expenses

Add business expense.

## Dashboard & Analytics

### GET /dashboard/stats

Get dashboard statistics for current user.

**Response:**
```json
{
  "total_bookings": 25,
  "completed_sessions": 20,
  "upcoming_sessions": 3,
  "remaining_credits": 5,
  "days_until_expiry": 15,
  "favorite_trainer": "Alex Rodriguez",
  "most_booked_class": "Morning HIIT",
  "weekly_activity": [
    {
      "date": "2023-12-01",
      "sessions": 2
    }
  ]
}
```

## Error Codes

| Code | Description |
|------|-------------|
| 200  | Success |
| 201  | Created |
| 400  | Bad Request |
| 401  | Unauthorized |
| 403  | Forbidden |
| 404  | Not Found |
| 422  | Validation Error |
| 500  | Server Error |

## Rate Limiting

API requests are limited to:
- **Authenticated users**: 1000 requests per hour
- **Unauthenticated users**: 100 requests per hour

## Pagination

List endpoints support pagination:

```json
{
  "data": [...],
  "current_page": 1,
  "last_page": 5,
  "per_page": 15,
  "total": 75,
  "next_page_url": "http://api.example.com/users?page=2",
  "prev_page_url": null
}
```

## Testing

### Health Check

```http
GET /health
```

Returns server status and database connectivity.

### Example Requests

#### cURL Examples

```bash
# Login
curl -X POST http://localhost:8001/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@sweat24.com","password":"password"}'

# Get classes (authenticated)
curl -X GET http://localhost:8001/api/v1/classes \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"

# Create booking
curl -X POST http://localhost:8001/api/v1/bookings \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"class_id":1,"notes":"Excited for this class!"}'
```

#### JavaScript/Fetch Examples

```javascript
// Login
const response = await fetch('/api/v1/auth/login', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
  },
  body: JSON.stringify({
    email: 'user@example.com',
    password: 'password'
  })
});

const { user, token } = await response.json();

// Authenticated request
const classes = await fetch('/api/v1/classes', {
  headers: {
    'Authorization': `Bearer ${token}`,
    'Accept': 'application/json'
  }
});
```

## SDK and Libraries

### JavaScript/TypeScript

The customer app includes TypeScript service layers in `/src/services/` that can be used as reference for API integration.

### PHP

Standard Laravel HTTP client or Guzzle can be used for server-to-server communication.

## Webhooks

Coming soon: Webhook support for real-time notifications.

## Versioning

API versioning is handled via URL path (`/api/v1/`). Breaking changes will result in a new version.

## Support

For API support:
- Check this documentation
- Review the source code
- Contact the development team

## Changelog

### v1.0.0
- Initial API release
- Authentication with Sanctum
- Core CRUD operations
- Dashboard analytics
- Financial management