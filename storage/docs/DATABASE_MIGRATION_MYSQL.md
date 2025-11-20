# SQLite to MySQL Migration Guide

## Overview
This guide covers the complete migration process from SQLite to MySQL for the Sweat93 Laravel application.

## Files Created
- `database/mysql_export.php` - Exports SQLite data to MySQL-compatible SQL
- `database/mysql_data_export.sql` - The exported data (already generated)
- `database/setup_mysql.sh` - MySQL database setup script
- `database/migrate_to_mysql.php` - Complete migration helper
- `.env.mysql` - Example MySQL configuration
- New MySQL-compatible migrations for problematic files

## Migration Steps

### 1. Setup MySQL Database
```bash
# Run the setup script (requires MySQL root access)
./database/setup_mysql.sh
```

This will create:
- Database: `sweat93_db`
- User: `sweat93_user`
- Password: Set in the script (change it!)

### 2. Update Environment Configuration
```bash
# Backup current .env
cp .env .env.sqlite.backup

# Update .env with MySQL settings
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sweat93_db
DB_USERNAME=sweat93_user
DB_PASSWORD=your_secure_password
```

### 3. Clear Laravel Caches
```bash
php artisan config:clear
php artisan cache:clear
```

### 4. Run Migrations
```bash
# Create fresh MySQL schema
php artisan migrate:fresh

# Or if you want to preserve migration history:
php artisan migrate
```

### 5. Import Data
```bash
# Import the exported data
mysql -u sweat93_user -p sweat93_db < database/mysql_data_export.sql
```

### 6. Verify Migration
```bash
# Check database connection
php artisan tinker
>>> DB::connection()->getPdo()->getAttribute(PDO::ATTR_DRIVER_NAME);
# Should return: "mysql"

# Check data
>>> User::count();
>>> Order::count();
```

## Fixed Migration Files

The following migrations were problematic as they used DB::statement() with database-specific SQL:

1. **Payment Method Enum Updates**
   - Old: Used ALTER TABLE with MySQL-specific ENUM syntax
   - New: Uses Laravel's Schema builder with string columns

2. **User Status Updates**  
   - Old: Used direct ENUM modifications
   - New: Uses string columns for flexibility

3. **Registration Status Updates**
   - Old: Database-specific ENUM handling
   - New: Portable string column approach

## Important Notes

### Data Type Changes
- SQLite `INTEGER` → MySQL `BIGINT` for IDs
- SQLite `TEXT` → MySQL `VARCHAR/TEXT` based on usage
- SQLite doesn't enforce ENUMs → MySQL uses proper validation

### Validation
- Payment methods are now validated in controllers/models
- Status fields use string validation instead of database ENUMs
- This provides more flexibility for future changes

### Rollback Plan
If you need to rollback to SQLite:
1. Restore `.env.sqlite.backup` to `.env`
2. Clear caches: `php artisan config:clear`
3. Your SQLite database is still at `database/database.sqlite`

## Troubleshooting

### Connection Refused
- Ensure MySQL is running: `sudo service mysql status`
- Check credentials in `.env`

### Migration Errors
- Run: `php database/migrate_to_mysql.php` to mark SQLite-specific migrations as run
- Check for foreign key constraints

### Data Import Issues
- Ensure foreign key checks are disabled (handled in export)
- Check for duplicate key errors
- Verify character encoding (UTF8MB4)

## Production Deployment
1. Backup production database first
2. Test migration on staging environment
3. Schedule maintenance window
4. Run migration during low-traffic period
5. Monitor application logs after migration