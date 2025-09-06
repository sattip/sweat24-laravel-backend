# SWEAT24 Laravel Backend API

This is a Laravel 12 backend API for the SWEAT24 Gym Management System providing RESTful APIs for managing gym operations including users, trainers, classes, bookings, packages, and financial data.

Always reference these instructions first and fallback to search or bash commands only when you encounter unexpected information that does not match the info here.

## Working Effectively

### Bootstrap and Setup (CRITICAL - Follow This Order)
1. **Install PHP dependencies**:
   ```bash
   composer install --no-interaction
   ```
   - **TIMING**: Takes 2-3 minutes normally. NEVER CANCEL - Set timeout to 300+ seconds.
   - **NETWORK ISSUES**: If GitHub API timeout occurs, composer will fallback to source downloads automatically.
   - If network is completely disabled, use: `COMPOSER_DISABLE_NETWORK=1 composer install`

2. **Environment setup**:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   - **TIMING**: ~1 second each command.

3. **Database setup (SQLite for development)**:
   ```bash
   touch database/database.sqlite
   php artisan migrate --seed
   ```
   - **TIMING**: Migrations take ~1 second. NEVER CANCEL.
   - **CRITICAL**: Two migrations have order issues and are disabled (`.skip` files):
     - `2025_01_25_120000_add_cancellation_policy_to_gym_classes.php.skip`
     - `2025_07_21_193610_add_pending_approval_to_registration_status_mysql.php.skip`
   - **SEEDING ISSUES**: Some seeders fail due to duplicate data. Use `php artisan db:seed --class=AdminSeeder` for basic setup.

4. **Test the setup**:
   ```bash
   php artisan serve --port=8001
   ```
   - **TIMING**: Starts immediately. Server runs on http://127.0.0.1:8001
   - **VALIDATION**: Test with: `curl http://127.0.0.1:8001/api/v1/auth/register` (expects validation errors)

### Frontend Assets (Node.js/Vite)
1. **Install Node.js dependencies**:
   ```bash
   npm install
   ```
   - **TIMING**: ~5 seconds. NEVER CANCEL - Set timeout to 180+ seconds.

2. **Build assets**:
   ```bash
   npm run build
   ```
   - **TIMING**: ~2 seconds for production build.

3. **Development assets**:
   ```bash
   npm run dev
   ```
   - **TIMING**: Starts immediately for development watching.

### Testing and Quality
1. **Run PHP tests**:
   ```bash
   php artisan test
   ```
   - **TIMING**: ~4 seconds. NEVER CANCEL - Set timeout to 300+ seconds.
   - **CURRENT STATE**: 65 tests fail, 6 pass due to route/model issues. Core framework works correctly.

2. **Code formatting**:
   ```bash
   ./vendor/bin/pint --test  # Check formatting issues
   ./vendor/bin/pint         # Fix formatting issues
   ```
   - **TIMING**: Check takes ~48 seconds, fix takes ~60+ seconds. NEVER CANCEL - Set timeout to 300+ seconds.
   - **CURRENT STATE**: 283 style issues across 360 files. Always run after changes.

## Validation Scenarios

### Always test these workflows after making changes:
1. **Server startup**: `php artisan serve --port=8001` should start without errors
2. **Database connection**: `php artisan migrate:status` should show migration status
3. **API endpoints**: Test registration endpoint (will show validation errors but API responds)
4. **Asset compilation**: `npm run build` should complete successfully
5. **Code style**: `./vendor/bin/pint --test` should run (may show issues but shouldn't crash)

### Test user accounts (from AdminSeeder):
- **Admin**: admin@sweat24.gr / password
- **Manager**: john.admin@sweat24.gr / admin123
- **Manager**: maria.manager@sweat24.gr / admin123

## Critical Warnings

### **NEVER CANCEL BUILDS OR LONG-RUNNING COMMANDS**
- **Composer install**: May take 3+ minutes with network issues - Set timeout to 300+ seconds
- **Laravel Pint**: Takes 48-60+ seconds to analyze/fix code - Set timeout to 300+ seconds
- **Database operations**: Usually fast (~1 second) but set timeout to 120+ seconds for safety

### **Known Issues to Expect**
- **Migration order issues**: Two migrations are disabled due to table dependency problems
- **Test failures**: Most tests fail due to route/model mismatches, but core Laravel functionality works
- **Code style issues**: 283+ style violations exist across the codebase
- **Seeder conflicts**: Some seeders have duplicate data issues
- **Network dependencies**: Composer may require GitHub fallbacks for package downloads

## Repository Structure

### Key Directories
- **`app/`**: Laravel application code (Controllers, Models, Services, etc.)
- **`database/migrations/`**: Database schema definitions (some have order issues)
- **`database/seeders/`**: Test data generation (some have duplicate issues)
- **`routes/api.php`**: API route definitions (45KB file with extensive endpoints)
- **`tests/`**: PHPUnit test suite (many failing but framework works)
- **`config/`**: Laravel configuration files
- **`public/`**: Web server document root

### Important Files
- **`composer.json`**: PHP dependencies (Laravel 12, Sanctum, Pusher, PHPUnit)
- **`package.json`**: Node.js dependencies (Vite, Tailwind, Laravel Echo)
- **`.env.example`**: Environment configuration template
- **`artisan`**: Laravel command-line interface

## API Features
- **Authentication**: Laravel Sanctum with `/api/v1/auth/*` endpoints
- **Core Resources**: Users, instructors, classes, bookings, packages
- **Financial**: Payment tracking, cash register, expense management
- **Real-time**: Pusher integration for notifications and chat
- **Advanced**: Loyalty points, referrals, evaluations, wait lists

## Development Commands Reference

### Database
```bash
php artisan migrate               # Run migrations
php artisan migrate:rollback      # Rollback last migration
php artisan migrate:status        # Check migration status
php artisan db:seed              # Run all seeders (may fail)
php artisan db:seed --class=AdminSeeder  # Run specific seeder
```

### Caching
```bash
php artisan config:cache         # Cache configuration
php artisan route:cache          # Cache routes
php artisan view:cache           # Cache views
php artisan config:clear         # Clear config cache
```

### Common Issues and Fixes
1. **Migration dependency errors**: Check for `.skip` files and migration order
2. **Composer network timeouts**: Command will fallback to source automatically
3. **Test failures**: Expected due to route/model issues, focus on server startup
4. **Style violations**: Use `./vendor/bin/pint` to auto-fix most issues
5. **Missing seeders**: Use specific seeder classes instead of DatabaseSeeder

## Production Notes
- **Database**: Configured for MySQL in production (see `.env.production.example`)
- **Queue**: Configured for database queues
- **Cache**: Configured for database caching
- **Session**: Configured for database sessions
- **Broadcasting**: Pusher integration available

Always validate your changes by testing server startup, API responses, and asset compilation before considering your work complete.