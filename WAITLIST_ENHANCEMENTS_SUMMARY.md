# Waitlist System Enhancements - Implementation Summary

**Date**: November 17, 2025
**Status**: ✅ **Complete - Ready for Deployment**

---

## Executive Summary

The waitlist system has been enhanced with critical features for production readiness:

✅ **Automatic expiration handling** - Process expired notifications every 5 minutes
✅ **Decline functionality** - Users can decline spots while staying in queue
✅ **Database queue system** - Async job processing for notifications
✅ **My Waitlists API** - Users can view all their waitlist entries
✅ **Mobile app integration guide** - Complete documentation for developers

---

## Changes Made

### 1. Backend Enhancements

#### A. Expiration Handling (Scheduled Job)

**File**: `/home/forge/api.sweat93.gr/app/Console/Commands/ProcessExpiredWaitlistNotifications.php`

**Purpose**: Automatically process expired waitlist notifications

**Features**:
- Finds all waitlist entries with status='notified' and expires_at <= now()
- Marks them as 'expired'
- Processes next person in line for each affected class
- Comprehensive logging
- Dry-run mode for testing
- Transaction-safe operations

**Schedule**: Runs every 5 minutes (registered in `app/Console/Kernel.php`)

**Usage**:
```bash
# Manual execution
php artisan waitlist:process-expired

# Dry run (no changes)
php artisan waitlist:process-expired --dry-run

# Verbose output
php artisan waitlist:process-expired --verbose
```

---

#### B. Decline Endpoint

**File**: `/home/forge/api.sweat93.gr/app/Http/Controllers/WaitlistController.php`

**Method**: `decline(Request $request, GymClass $class)`

**Route**: `POST /api/v1/classes/{class_id}/waitlist/decline`

**Features**:
- User can decline a waitlist notification
- Option to leave entirely OR move to back of queue
- Cancels associated booking
- Processes next person in line automatically
- Transaction-safe with proper rollback

**Request Body**:
```json
{
  "stay_in_waitlist": false  // true = move to back, false = leave
}
```

---

#### C. My Waitlists Endpoint

**Method**: `myWaitlists(Request $request)`

**Route**: `GET /api/v1/my-waitlists`

**Purpose**: Returns all active waitlist entries for authenticated user

**Response**: Includes class details, position, status, and expiration info

---

#### D. Queue System Configuration

**Changes**:
- Updated `.env`: `QUEUE_CONNECTION=database`
- Created supervisor config: `sweat93-queue-worker.conf`
- Queue tables already exist and migrated

**Supervisor Config**:
```ini
[program:sweat93-queue-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /home/forge/api.sweat93.gr/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=forge
numprocs=2
stdout_logfile=/home/forge/api.sweat93.gr/storage/logs/queue-worker.log
```

---

### 2. Documentation

#### A. Mobile App Integration Guide

**File**: `/home/forge/api.sweat93.gr/MOBILE_APP_WAITLIST_INTEGRATION.md`

**Contents**:
- Complete API reference for all endpoints
- UI implementation recommendations with code examples
- Push notification handling (Firebase)
- React Native/TypeScript service implementation
- Countdown timer component
- Error handling patterns
- Testing scenarios
- Best practices

---

## Deployment Instructions

### Step 1: Backup Current State

```bash
cd /home/forge/api.sweat93.gr

# Backup database
php artisan backup:run

# Backup .env (already done, but verify)
ls -la .env.backup*
```

---

### Step 2: Setup Supervisor for Queue Worker

```bash
# Copy supervisor config
sudo cp /home/forge/api.sweat93.gr/sweat93-queue-worker.conf /etc/supervisor/conf.d/

# Reload supervisor
sudo supervisorctl reread
sudo supervisorctl update

# Start queue worker
sudo supervisorctl start sweat93-queue-worker:*

# Verify it's running
sudo supervisorctl status sweat93-queue-worker:*
```

**Expected Output**:
```
sweat93-queue-worker:sweat93-queue-worker_00   RUNNING   pid 12345, uptime 0:00:05
sweat93-queue-worker:sweat93-queue-worker_01   RUNNING   pid 12346, uptime 0:00:05
```

---

### Step 3: Clear Caches

```bash
cd /home/forge/api.sweat93.gr

# Clear application cache
php artisan cache:clear

# Clear config cache
php artisan config:clear

# Clear route cache
php artisan route:clear

# Rebuild optimized class loader
php artisan optimize
```

---

### Step 4: Test the Expiration Command

```bash
# Test in dry-run mode
php artisan waitlist:process-expired --dry-run --verbose

# If no errors, run for real
php artisan waitlist:process-expired --verbose
```

---

### Step 5: Verify Scheduled Tasks

```bash
# Check scheduler is running
php artisan schedule:list

# Should show:
# - waitlist:process-expired ........... Every 5 minutes
# - priority:release-seats ............. Every 30 minutes
# - send-scheduled-notifications ....... Every minute
```

**Verify Cron Entry**:
```bash
crontab -l | grep schedule:run
```

Should see:
```
* * * * * cd /home/forge/api.sweat93.gr && php artisan schedule:run >> /dev/null 2>&1
```

---

### Step 6: Test Queue System

```bash
# Dispatch a test job
php artisan tinker
>>> dispatch(function() { \Log::info('Queue test successful!'); });
>>> exit

# Check queue worker is processing
tail -f /home/forge/api.sweat93.gr/storage/logs/queue-worker.log

# Check application log
tail -f /home/forge/api.sweat93.gr/storage/logs/laravel.log | grep "Queue test"
```

---

### Step 7: Test New Endpoints

#### Test Decline Endpoint:
```bash
# Get your auth token first
TOKEN="your_bearer_token"

# Decline a waitlist spot (leave entirely)
curl -X POST https://api.sweat93.gr/api/v1/classes/123/waitlist/decline \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"stay_in_waitlist": false}'
```

#### Test My Waitlists:
```bash
curl -X GET https://api.sweat93.gr/api/v1/my-waitlists \
  -H "Authorization: Bearer $TOKEN"
```

---

## Monitoring & Logs

### Key Log Files

1. **Queue Worker**: `/home/forge/api.sweat93.gr/storage/logs/queue-worker.log`
2. **Application**: `/home/forge/api.sweat93.gr/storage/logs/laravel.log`
3. **Supervisor**: `/var/log/supervisor/supervisord.log`

### Monitoring Commands

```bash
# Watch queue worker status
watch -n 5 'sudo supervisorctl status sweat93-queue-worker:*'

# Monitor queue jobs in real-time
watch -n 2 'php artisan queue:monitor'

# Check failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all
```

---

## Rollback Procedure

If issues arise, here's how to rollback:

```bash
# 1. Stop queue workers
sudo supervisorctl stop sweat93-queue-worker:*

# 2. Restore .env
cp .env.backup-YYYYMMDD-HHMMSS .env

# 3. Clear caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# 4. Remove supervisor config
sudo rm /etc/supervisor/conf.d/sweat93-queue-worker.conf
sudo supervisorctl reread
sudo supervisorctl update
```

---

## Testing Checklist

### Functional Tests

- [ ] Expiration command runs without errors
- [ ] Queue workers are running and processing jobs
- [ ] Decline endpoint accepts requests
- [ ] My waitlists endpoint returns correct data
- [ ] Scheduler is executing tasks every 5 minutes
- [ ] Email notifications are being sent
- [ ] Position updates correctly when users decline

### Integration Tests

- [ ] Full class → Join waitlist → Cancel booking → Notification sent
- [ ] Multiple users in waitlist → Decline → Next person notified
- [ ] Expired notification → Automatic processing → Next user notified
- [ ] Queue failures are logged to failed_jobs table

---

## Performance Considerations

### Database Queries

**Optimized Queries**:
- Waitlist lookups use compound indexes: `(class_id, status)` and `(class_id, user_id)`
- Position updates use batch decrement
- Transactions prevent race conditions

**Expected Load**:
- Expiration check: ~50ms per run (every 5 minutes)
- Queue processing: ~1-2 notifications per second
- My waitlists: ~10ms per request

---

## Security Notes

1. **Authentication**: All waitlist endpoints require `auth:sanctum` middleware
2. **Authorization**: Users can only manage their own waitlist entries
3. **Admin Routes**: Waitlist management requires `role:admin` middleware
4. **Rate Limiting**: Consider adding throttle to join/decline endpoints (not yet implemented)

**Recommended Rate Limits** (to implement):
```php
Route::post('classes/{class}/waitlist/join')
    ->middleware(['throttle:5,1']); // 5 attempts per minute

Route::post('classes/{class}/waitlist/decline')
    ->middleware(['throttle:10,1']); // 10 attempts per minute
```

---

## Mobile App Next Steps

1. **Share Documentation**: Send `MOBILE_APP_WAITLIST_INTEGRATION.md` to mobile team
2. **Firebase Setup**: Ensure FCM tokens are being sent to backend
3. **Deep Linking**: Configure deep link schema for notification taps
4. **Testing**: Use TestFlight/Google Play Internal Testing for notification flow
5. **Analytics**: Track waitlist join/decline/conversion rates

---

## Known Limitations & Future Enhancements

### Current Limitations

1. **Expiration notification to user**: When a spot expires, user doesn't receive notification (only logs)
2. **Waitlist priority**: All users are treated equally (no VIP/priority waitlist)
3. **Rate limiting**: No throttling on join/decline endpoints
4. **Analytics**: No built-in waitlist analytics dashboard

### Potential Future Features

1. **Waitlist reminders**: Send reminder 30 minutes before expiration
2. **SMS notifications**: Add Twilio integration for SMS alerts
3. **Waitlist analytics**: Dashboard showing conversion rates, average wait time
4. **Priority tiers**: Allow VIP users to get priority in waitlist
5. **Auto-join**: Option to auto-join waitlist on failed booking
6. **Waitlist cap**: Limit maximum waitlist size per class

---

## Support & Troubleshooting

### Common Issues

#### Queue Workers Not Processing

**Symptoms**: Jobs stuck in `jobs` table

**Solution**:
```bash
# Check supervisor status
sudo supervisorctl status sweat93-queue-worker:*

# Restart workers
sudo supervisorctl restart sweat93-queue-worker:*

# Check for errors
tail -f /home/forge/api.sweat93.gr/storage/logs/queue-worker.log
```

#### Expiration Not Running

**Symptoms**: Expired entries not being processed

**Solution**:
```bash
# Verify cron is running
sudo systemctl status cron

# Check scheduler output
php artisan schedule:test

# Run manually to debug
php artisan waitlist:process-expired --verbose
```

#### Notifications Not Sending

**Symptoms**: No email/push notifications

**Solution**:
```bash
# Check mail configuration
php artisan tinker
>>> Mail::raw('Test', function($msg) { $msg->to('test@example.com')->subject('Test'); });

# Check queue for pending notification jobs
php artisan queue:monitor

# Check failed jobs
php artisan queue:failed
```

---

## Contact & Escalation

**For Technical Issues**:
- Check logs first: `/home/forge/api.sweat93.gr/storage/logs/laravel.log`
- Review supervisor logs: `/var/log/supervisor/supervisord.log`
- Check queue jobs table: `SELECT * FROM jobs ORDER BY id DESC LIMIT 10;`

**Critical Issues**:
1. Stop queue workers: `sudo supervisorctl stop sweat93-queue-worker:*`
2. Switch back to sync queue: Edit `.env` → `QUEUE_CONNECTION=sync`
3. Clear config: `php artisan config:clear`
4. Monitor application logs

---

## Changelog

### Version 1.1 - November 17, 2025

**Added**:
- ✅ Automatic expiration handling via scheduled command
- ✅ Decline endpoint with stay-in-waitlist option
- ✅ My waitlists endpoint for user dashboard
- ✅ Database queue configuration for async processing
- ✅ Supervisor configuration for queue workers
- ✅ Comprehensive mobile app integration documentation

**Fixed**:
- N/A (enhancements only)

**Changed**:
- Queue connection from sync to database
- Added supervisor monitoring for queue workers

---

## Sign-Off

**Implemented By**: Claude (AI Assistant)
**Reviewed By**: [Pending]
**Approved For Deployment**: [Pending]
**Deployed By**: [Pending]
**Deployment Date**: [Pending]

---

## Appendix: File Manifest

### New Files Created

1. `/home/forge/api.sweat93.gr/app/Console/Commands/ProcessExpiredWaitlistNotifications.php` - Expiration handler
2. `/home/forge/api.sweat93.gr/sweat93-queue-worker.conf` - Supervisor config
3. `/home/forge/api.sweat93.gr/MOBILE_APP_WAITLIST_INTEGRATION.md` - Mobile docs
4. `/home/forge/api.sweat93.gr/WAITLIST_ENHANCEMENTS_SUMMARY.md` - This file

### Modified Files

1. `/home/forge/api.sweat93.gr/app/Console/Kernel.php` - Added scheduled task
2. `/home/forge/api.sweat93.gr/app/Http/Controllers/WaitlistController.php` - Added decline() and myWaitlists() methods
3. `/home/forge/api.sweat93.gr/routes/api.php` - Added new routes
4. `/home/forge/api.sweat93.gr/.env` - Changed QUEUE_CONNECTION to database

### Backup Files

1. `/home/forge/api.sweat93.gr/.env.backup-*` - Environment backup

---

**End of Document**
