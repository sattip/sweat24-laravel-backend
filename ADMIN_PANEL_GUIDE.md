# SWEAT24 Admin Panel Guide

## Overview

The SWEAT24 Laravel backend now includes a built-in admin panel for managing administrator users. This UI provides a secure interface for creating, editing, and managing admin accounts.

## Features

- 🔐 Secure admin login with session management
- 👥 Admin user management (Create, Read, Update, Delete)
- 📊 Dashboard with key statistics
- 🎨 Modern, responsive UI using Tailwind CSS
- 🔒 Role-based access control

## Access URLs

- **Admin Login**: `https://your-domain.com/admin/login`
- **Admin Dashboard**: `https://your-domain.com/admin/dashboard`
- **Admin Users**: `https://your-domain.com/admin/users`

## Default Admin Credentials

```
Email: admin@sweat24.gr
Password: password
```

**Important**: Change these credentials immediately after first login!

## Setup Instructions

### 1. Run Migrations
```bash
php artisan migrate
```

### 2. Seed Admin Users
```bash
# Seed all data including admin users
php artisan db:seed

# Or seed only admin users
php artisan db:seed --class=AdminSeeder
```

### 3. Clear Caches
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

## Admin Panel Features

### Dashboard
- View total users count
- See admin users count
- Monitor active users
- Track total revenue

### Admin User Management

#### List Admin Users
- View all administrators
- See status (Active/Inactive)
- Quick actions for edit/delete

#### Create New Admin
1. Navigate to Admin Users
2. Click "Add New Admin"
3. Fill in required fields:
   - Full Name
   - Email Address
   - Password (min 8 characters)
   - Phone (optional)
4. Click "Create Admin User"

#### Edit Admin User
1. Click "Edit" on any admin user
2. Update information as needed
3. Change password (optional)
4. Update status (Active/Inactive)
5. Save changes

#### Delete Admin User
- Click "Delete" on any admin user
- Confirm deletion
- Note: You cannot delete your own account

## Security Features

### Middleware Protection
- All admin routes are protected by `AdminMiddleware`
- Only users with `membership_type = 'Admin'` can access
- Session-based authentication for web interface

### Password Requirements
- Minimum 8 characters
- Must be confirmed when creating/updating

### Session Management
- Automatic logout on inactivity
- "Remember Me" functionality
- Secure session handling

## Development

### Adding New Admin Features

1. **Create Controller**:
```php
php artisan make:controller Admin/YourController
```

2. **Add Routes** in `routes/web.php`:
```php
Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
    Route::resource('your-feature', YourController::class);
});
```

3. **Create Views** in `resources/views/admin/`:
- Extend the admin layout: `@extends('layouts.admin')`
- Add your content in `@section('content')`

### Customizing the Admin Layout

Edit `/resources/views/layouts/admin.blade.php` to:
- Add new menu items
- Change styling
- Add JavaScript functionality

## Troubleshooting

### Can't Access Admin Panel
1. Ensure user has `membership_type = 'Admin'`
2. Clear browser cookies
3. Check Laravel logs

### Login Issues
1. Verify credentials in database
2. Check password hash
3. Ensure sessions are configured properly

### Middleware Not Working
1. Clear route cache: `php artisan route:clear`
2. Check middleware registration in `bootstrap/app.php`
3. Verify AdminMiddleware class exists

## API vs Web Authentication

The system supports both:
- **API Authentication**: Using Sanctum tokens for the React admin panel
- **Web Authentication**: Using sessions for the built-in admin UI

Both can coexist without conflicts.

## Production Deployment

1. Set proper environment variables
2. Use HTTPS for security
3. Configure session driver (redis/database recommended)
4. Set up proper CSRF protection
5. Monitor failed login attempts

## Additional Admin Users

The seeder creates three admin accounts:

| Name | Email | Password |
|------|-------|----------|
| Admin User | admin@sweat24.gr | password |
| John Administrator | john.admin@sweat24.gr | admin123 |
| Maria Manager | maria.manager@sweat24.gr | admin123 |

Remember to change all default passwords in production!