# Client Profile Management - SWEAT24

This document describes the client profile editing functionality for gym members.

## Features Implemented

### 1. Profile Editing
Members can edit their personal information including:
- Name, email, and phone number
- Physical address
- Date of birth
- Emergency contact information
- Medical history and notes
- Profile picture upload

### 2. Password Management
- Secure password change functionality
- Current password verification required
- Password strength requirements enforced
- Confirmation field to prevent typos

### 3. Notification Preferences
Members can control how they receive notifications:
- Email notifications
- SMS notifications
- Push notifications
- Booking reminders
- Package expiry alerts
- Promotional emails opt-in/out

### 4. Privacy Settings
Members can manage their privacy preferences:
- Profile visibility to trainers
- Attendance history sharing
- Photo permissions for gym marketing
- Progress report sharing

### 5. Booking Management
- View upcoming and past bookings
- Add/edit notes for bookings
- Cancel bookings (with policy enforcement)
- Reschedule requests

### 6. Account Management
- Request account deactivation
- Provide feedback when leaving

## API Endpoints

All endpoints require authentication via Sanctum.

### Profile Management
- `GET /api/v1/profile` - Get user profile
- `PUT /api/v1/profile` - Update profile information
- `PUT /api/v1/profile/password` - Change password
- `POST /api/v1/profile/avatar` - Upload profile picture

### Preferences
- `GET /api/v1/profile/notification-preferences` - Get notification preferences
- `PUT /api/v1/profile/notification-preferences` - Update notification preferences
- `GET /api/v1/profile/privacy-settings` - Get privacy settings
- `PUT /api/v1/profile/privacy-settings` - Update privacy settings

### Bookings
- `GET /api/v1/profile/booking-history` - Get user's booking history
- `PUT /api/v1/profile/bookings/{booking}/notes` - Update booking notes

### Account
- `POST /api/v1/profile/deactivation-request` - Request account deactivation

## Setup Instructions

1. **Run migrations**:
   ```bash
   php artisan migrate
   ```

2. **Install Vue.js dependencies** (if not already installed):
   ```bash
   npm install vue@next
   npm install axios
   ```

3. **Build assets**:
   ```bash
   npm run dev
   # or for production
   npm run build
   ```

4. **Create test data** (optional):
   ```bash
   php artisan db:seed --class=ClientTestSeeder
   ```

## Testing

### Test Credentials
After running the seeder:
- Email: `member@example.com`
- Password: `password123`

### Access the Client Dashboard
Navigate to: `/client/dashboard`

## Security Features

1. **Authentication**: All routes require authenticated users with 'member' role
2. **Password Security**: 
   - Minimum 8 characters
   - Mixed case, numbers, and symbols required in production
   - Current password verification for changes
3. **CSRF Protection**: All forms include CSRF tokens
4. **Input Validation**: Server-side validation for all inputs
5. **File Upload Security**: Only images allowed for avatars, max 2MB

## Mobile Responsiveness

The interface is fully responsive and optimized for:
- Desktop (1200px+)
- Tablet (768px - 1199px)
- Mobile (< 768px)

## Customization

### Styling
- Bootstrap 5 is used for base styling
- Custom styles are in the Vue component `<style>` sections
- Primary color can be changed via Bootstrap variables

### Validation Rules
- Edit `app/Http/Requests/UpdateProfileRequest.php` for profile validation
- Password rules configured in `app/Providers/AppServiceProvider.php`

### Adding New Fields
1. Add migration for database fields
2. Update User model `$fillable` array
3. Add fields to `ClientProfileController`
4. Update Vue components with new form fields

## Troubleshooting

### Common Issues

1. **"Class not found" errors**:
   ```bash
   composer dump-autoload
   ```

2. **JavaScript not updating**:
   ```bash
   npm run dev
   php artisan cache:clear
   ```

3. **Migration errors**:
   Check if fields already exist in the database

### Debug Mode
Enable debug mode in `.env`:
```
APP_DEBUG=true
```

## Future Enhancements

1. **Two-factor authentication**
2. **Email verification for changes**
3. **Profile completion progress bar**
4. **Social media integration**
5. **Fitness goal tracking**
6. **Progress photo uploads**
7. **Integration with wearable devices**

## Support

For issues or questions, please contact the development team.