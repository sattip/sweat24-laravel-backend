# Sweat93 Email System Documentation

## Overview
This document provides comprehensive information about the email notification system implemented for Sweat93 Gym Management System.

## Configuration

### Environment Variables (.env)
```env
# Mail Configuration
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com          # Replace with your SMTP host
MAIL_PORT=587                     # Common ports: 587 (TLS), 465 (SSL), 25 (unencrypted)
MAIL_USERNAME=your_email@gmail.com # Your SMTP username
MAIL_PASSWORD=your_app_password    # Your SMTP password or app-specific password
MAIL_ENCRYPTION=tls                # Options: tls, ssl, or null
MAIL_FROM_ADDRESS=info@sweat93.gr  # Default "from" address
MAIL_FROM_NAME="${APP_NAME}"       # Default "from" name

# Admin Email Settings
ADMIN_EMAIL=admin@sweat93.gr      # Where admin notifications are sent
ADMIN_NAME="Sweat93 Admin"        # Admin display name
```

### Gmail Configuration
If using Gmail:
1. Enable 2-factor authentication
2. Generate an app-specific password
3. Use the app password in MAIL_PASSWORD

### Other SMTP Providers
- **Mailgun**: Set MAIL_HOST=smtp.mailgun.org
- **SendGrid**: Set MAIL_HOST=smtp.sendgrid.net
- **Amazon SES**: Set MAIL_HOST=email-smtp.[region].amazonaws.com

## Email Templates

All email templates are located in `/resources/views/emails/` and are written in Greek.

### Template Structure
```
resources/views/emails/
├── layout.blade.php                    # Base layout with Sweat93 branding
├── auth/
│   ├── registration-confirmation.blade.php  # Επιβεβαίωση εγγραφής
│   ├── account-approved.blade.php          # Έγκριση λογαριασμού
│   ├── account-rejected.blade.php          # Απόρριψη λογαριασμού
│   └── password-reset.blade.php            # Επαναφορά κωδικού
├── bookings/
│   ├── booking-confirmation.blade.php      # Επιβεβαίωση κράτησης
│   ├── booking-cancelled.blade.php         # Ακύρωση κράτησης
│   ├── appointment-scheduled.blade.php     # Προγραμματισμός ραντεβού
│   └── waitlist-spot-available.blade.php   # Διαθέσιμη θέση από λίστα αναμονής
├── orders/
│   ├── order-confirmation.blade.php        # Επιβεβαίωση παραγγελίας
│   └── order-ready.blade.php              # Παραγγελία έτοιμη
├── payments/
│   ├── payment-received.blade.php          # Επιβεβαίωση πληρωμής
│   └── payment-overdue.blade.php          # Ληξιπρόθεσμη πληρωμή
├── packages/
│   ├── sessions-low.blade.php             # Χαμηλές συνεδρίες
│   └── package-expiring.blade.php         # Λήξη πακέτου
├── events/
│   ├── event-announcement.blade.php       # Ανακοίνωση εκδήλωσης
│   └── event-reminder.blade.php           # Υπενθύμιση εκδήλωσης
└── admin/
    ├── new-registration.blade.php         # Νέα εγγραφή (για admin)
    └── new-booking-request.blade.php      # Νέο αίτημα κράτησης (για admin)
```

## Notification Classes

All notification classes are located in `/app/Notifications/`:

### User Notifications

#### Authentication
- `Auth\RegistrationConfirmationNotification` - Sent when user registers
- `Auth\AccountApprovedNotification` - Sent when admin approves account
- `Auth\AccountRejectedNotification` - Sent when admin rejects account
- `Auth\ResetPasswordNotification` - Sent for password reset requests

#### Bookings
- `Bookings\BookingConfirmationNotification` - Sent when booking is confirmed
- `Bookings\BookingCancelledNotification` - Sent when booking is cancelled
- `Bookings\AppointmentScheduledNotification` - Sent when appointment is scheduled
- `WaitlistSpotAvailableNotification` - Sent when spot becomes available

#### Orders
- `Orders\OrderConfirmationNotification` - Sent when order is placed
- `Orders\OrderReadyNotification` - Sent when order is ready for pickup

#### Payments
- `Payments\PaymentReceivedNotification` - Sent when payment is received
- `Payments\PaymentOverdueNotification` - Sent for overdue payments
- `Payments\PaymentInstallmentReceivedNotification` - Sent for installment payments

### Admin Notifications
- `Admin\NewRegistrationNotification` - Sent to admin for new registrations
- `Admin\NewBookingRequestNotification` - Sent to admin for new booking requests

## Email Triggers

### Registration Flow
1. **User registers** → `RegistrationConfirmationNotification` to user, `NewRegistrationNotification` to admin
2. **Admin approves** → `AccountApprovedNotification` to user
3. **Admin rejects** → `AccountRejectedNotification` to user

### Password Reset Flow
1. **User requests reset** → `ResetPasswordNotification` with reset link
2. **User resets password** → Success message (no email)

### Booking Flow
1. **User books class** → `BookingConfirmationNotification` to user
2. **User cancels booking** → `BookingCancelledNotification` to user
3. **Admin cancels booking** → `BookingCancelledNotification` with admin reason
4. **Waitlist spot available** → `WaitlistSpotAvailableNotification` to user

### Appointment Flow
1. **User requests appointment** → `NewBookingRequestNotification` to admin
2. **Admin schedules appointment** → `AppointmentScheduledNotification` to user

### Order Flow
1. **User places order** → `OrderConfirmationNotification` to user
2. **Order ready for pickup** → `OrderReadyNotification` to user

### Payment Flow
1. **Payment received** → `PaymentReceivedNotification` to user
2. **Payment overdue** → `PaymentOverdueNotification` to user

## Testing

### Test Commands

#### 1. Test SMTP Configuration
```bash
php artisan mail:test your-email@domain.com
```
This sends a simple test email to verify SMTP settings.

#### 2. Test All Email Templates
```bash
php artisan email:test --email=your-email@domain.com --type=all
```

#### 3. Test Specific Email Category
```bash
# Test only registration emails
php artisan email:test --email=your-email@domain.com --type=registration

# Test only booking emails
php artisan email:test --email=your-email@domain.com --type=booking

# Test only order emails
php artisan email:test --email=your-email@domain.com --type=order

# Test only payment emails
php artisan email:test --email=your-email@domain.com --type=payment
```

### Testing in Development
For development, you can use the `log` driver to write emails to the log file:
```env
MAIL_MAILER=log
```
Emails will appear in `/storage/logs/laravel.log`

## API Endpoints

### Password Reset
- `POST /api/v1/auth/forgot-password` - Request password reset
  ```json
  {
    "email": "user@example.com"
  }
  ```

- `POST /api/v1/auth/reset-password` - Reset password with token
  ```json
  {
    "token": "reset-token",
    "email": "user@example.com",
    "password": "newpassword",
    "password_confirmation": "newpassword"
  }
  ```

- `POST /api/v1/auth/validate-reset-token` - Validate reset token
  ```json
  {
    "token": "reset-token",
    "email": "user@example.com"
  }
  ```

## Queue Configuration

All notifications implement `ShouldQueue` for better performance. To process queued emails:

1. Configure queue driver in `.env`:
   ```env
   QUEUE_CONNECTION=database
   ```

2. Run migrations for queue tables:
   ```bash
   php artisan queue:table
   php artisan migrate
   ```

3. Start queue worker:
   ```bash
   php artisan queue:work
   ```

For production, use Supervisor to keep queue workers running.

## Troubleshooting

### Common Issues

#### 1. Emails not sending
- Check SMTP credentials in `.env`
- Verify firewall allows outbound connections on SMTP port
- Test with `php artisan mail:test`

#### 2. Emails going to spam
- Ensure SPF/DKIM records are configured for your domain
- Use a reputable SMTP service
- Avoid spam trigger words in content

#### 3. Queue not processing
- Ensure queue worker is running
- Check `failed_jobs` table for errors
- Clear cache: `php artisan cache:clear`

#### 4. Template not found
- Clear view cache: `php artisan view:clear`
- Check file permissions on template files

## Customization

### Changing Email Language
Templates are currently in Greek. To add English versions:
1. Create new templates with `-en` suffix
2. Check user's preferred language in notification classes
3. Use appropriate template based on language preference

### Adding New Email Types
1. Create new notification class in `/app/Notifications/`
2. Create corresponding Blade template in `/resources/views/emails/`
3. Implement notification trigger in relevant controller
4. Add test case to `TestEmailSystem` command

### Styling Emails
All styles are inline in the layout template for maximum email client compatibility.
To modify styles, edit `/resources/views/emails/layout.blade.php`.

## Security Considerations

1. **Password Reset Tokens**: Expire after 24 hours
2. **Email Verification**: Consider implementing email verification for new accounts
3. **Rate Limiting**: Add rate limiting to password reset endpoint
4. **Sanitization**: All user input is escaped in templates
5. **HTTPS Only**: Reset links should use HTTPS in production

## Maintenance

### Regular Tasks
1. Monitor `failed_jobs` table for failed email deliveries
2. Clean old entries from `password_resets` table
3. Review email bounce rates and complaints
4. Update SMTP credentials when changed
5. Test email deliverability monthly

### Logs
Email-related logs can be found in:
- `/storage/logs/laravel.log` - Application logs
- Queue worker logs (if using Supervisor)
- SMTP server logs (check with your provider)

## Support

For issues or questions about the email system:
1. Check this documentation
2. Review error logs
3. Test with the provided commands
4. Contact system administrator

---

Last Updated: December 2024
Version: 1.0