# SWEAT24 Laravel Backend API

This is the Laravel backend API for the SWEAT24 Gym Management System. It provides RESTful APIs for managing gym operations including users, trainers, classes, bookings, packages, and financial data.

## Features

- **Authentication**: Laravel Sanctum for API authentication
- **User Management**: Complete CRUD for gym members
- **Trainer Management**: Instructor profiles and specialties
- **Class Scheduling**: Gym classes with instructor assignments
- **Booking System**: Class reservations and check-ins
- **Package Management**: Membership packages and subscriptions
- **Financial Management**: Revenue tracking, expenses, and payment installments
- **Dashboard Analytics**: Real-time statistics and reporting

## Tech Stack

- **Framework**: Laravel 12
- **Database**: SQLite (development) / MySQL (production)
- **Authentication**: Laravel Sanctum
- **API**: RESTful JSON APIs
- **Architecture**: MVC with proper separation of concerns

## Installation

1. **Clone the repository**
```bash
git clone <repo-url>
cd sweat24-laravel-backend
```

2. **Install dependencies**
```bash
composer install
```

3. **Environment setup**
```bash
cp .env.example .env
php artisan key:generate
```

4. **Database setup**
```bash
php artisan migrate --seed
```

5. **Start the server**
```bash
php artisan serve --port=8001
```

The API will be available at `http://localhost:8001`

## API Endpoints

### Authentication
- `POST /api/v1/auth/login` - User login
- `POST /api/v1/auth/register` - User registration
- `POST /api/v1/auth/logout` - User logout
- `GET /api/v1/auth/me` - Get current user

### Core Resources
- `GET|POST /api/v1/users` - User management
- `GET|POST /api/v1/instructors` - Trainer management
- `GET|POST /api/v1/classes` - Class scheduling
- `GET|POST /api/v1/bookings` - Booking management
- `GET|POST /api/v1/packages` - Package management

### Financial
- `GET|POST /api/v1/payment-installments` - Payment tracking
- `GET|POST /api/v1/cash-register` - Cash flow management
- `GET|POST /api/v1/business-expenses` - Expense tracking
- `GET /api/v1/dashboard/stats` - Dashboard analytics

### Special Endpoints
- `POST /api/v1/bookings/{id}/check-in` - Check-in booking
- All endpoints require authentication except login/register

## Database Schema

### Core Entities
- **Users**: Gym members and administrators
- **Instructors**: Trainers with specialties and rates
- **Packages**: Membership packages with pricing
- **GymClasses**: Scheduled classes with instructors
- **Bookings**: Class reservations and attendance

### Financial Entities
- **PaymentInstallments**: Payment scheduling
- **CashRegisterEntries**: Daily cash flow
- **BusinessExpenses**: Expense tracking and approval

## Development

### Running Tests
```bash
php artisan test
```

### Code Standards
- Follow PSR-12 coding standards
- Use proper validation in controllers
- Implement proper error handling
- Document all API endpoints

### Seeding Data
```bash
php artisan db:seed
```

This creates sample data for:
- Admin user (admin@sweat24.com / password)
- 3 instructors with specialties
- 5 membership packages
- Sample classes and bookings
- Financial test data

## Security

- All API routes require Sanctum authentication
- CORS configured for frontend applications
- Input validation on all endpoints
- SQL injection protection via Eloquent ORM

## Frontend Integration

This backend is designed to work with:
- **Admin Panel**: React TypeScript admin dashboard
- **Customer App**: React customer mobile/web app

Example API usage:
```javascript
// Login
const response = await fetch('/api/v1/auth/login', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({ email, password })
});

// Authenticated requests
const classes = await fetch('/api/v1/classes', {
  headers: { 
    'Authorization': `Bearer ${token}`,
    'Accept': 'application/json'
  }
});
```

## Production Deployment

1. **Environment Variables**
```env
APP_ENV=production
APP_DEBUG=false
DB_CONNECTION=mysql
DB_HOST=your-db-host
DB_DATABASE=sweat24
SANCTUM_STATEFUL_DOMAINS=your-frontend-domain.com
```

2. **Optimize for production**
```bash
composer install --optimize-autoloader --no-dev
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

3. **Database Migration**
```bash
php artisan migrate --force
```

## License

This project is proprietary software for SWEAT24 Gym Management System.

## Support

For technical support or questions, contact the development team.