# SWEAT24 Laravel Backend Deployment Guide

This guide covers the deployment process for the SWEAT24 Laravel backend API.

## Prerequisites

- PHP 8.2 or higher
- Composer
- MySQL 8.0 or higher (for production)
- Web server (Apache/Nginx)
- SSL certificate for HTTPS

## Production Deployment

### 1. Server Setup

```bash
# Update system packages
sudo apt update && sudo apt upgrade -y

# Install PHP 8.2 and required extensions
sudo apt install php8.2 php8.2-fpm php8.2-mysql php8.2-xml php8.2-curl php8.2-zip php8.2-mbstring php8.2-tokenizer php8.2-bcmath

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install MySQL
sudo apt install mysql-server
```

### 2. Database Setup

```sql
-- Create database and user
CREATE DATABASE sweat24;
CREATE USER 'sweat24_user'@'localhost' IDENTIFIED BY 'secure_password';
GRANT ALL PRIVILEGES ON sweat24.* TO 'sweat24_user'@'localhost';
FLUSH PRIVILEGES;
```

### 3. Application Deployment

```bash
# Clone the repository
git clone https://github.com/sattip/sweat24-laravel-backend.git
cd sweat24-laravel-backend

# Install dependencies
composer install --optimize-autoloader --no-dev

# Set up environment
cp .env.production.example .env
nano .env  # Edit with your production values

# Generate application key
php artisan key:generate

# Run migrations and seed data
php artisan migrate --force
php artisan db:seed --force

# Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set proper permissions
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### 4. Web Server Configuration

#### Nginx Configuration

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name your-api-domain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name your-api-domain.com;
    root /var/www/sweat24-laravel-backend/public;

    # SSL Configuration
    ssl_certificate /path/to/certificate.crt;
    ssl_certificate_key /path/to/private.key;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-XSS-Protection "1; mode=block";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

#### Apache Configuration

```apache
<VirtualHost *:80>
    ServerName your-api-domain.com
    Redirect permanent / https://your-api-domain.com/
</VirtualHost>

<VirtualHost *:443>
    ServerName your-api-domain.com
    DocumentRoot /var/www/sweat24-laravel-backend/public

    # SSL Configuration
    SSLEngine on
    SSLCertificateFile /path/to/certificate.crt
    SSLCertificateKeyFile /path/to/private.key

    <Directory /var/www/sweat24-laravel-backend/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/sweat24_error.log
    CustomLog ${APACHE_LOG_DIR}/sweat24_access.log combined
</VirtualHost>
```

### 5. Environment Variables

Update your `.env` file with production values:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-api-domain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=sweat24
DB_USERNAME=sweat24_user
DB_PASSWORD=your_secure_password

# Configure for your frontend domains
SANCTUM_STATEFUL_DOMAINS=your-frontend-domain.com,your-admin-domain.com
SESSION_DOMAIN=.your-domain.com
```

## Security Configuration

### 1. Firewall Setup

```bash
# Enable UFW firewall
sudo ufw enable

# Allow SSH, HTTP, and HTTPS
sudo ufw allow ssh
sudo ufw allow 80
sudo ufw allow 443

# Allow MySQL only from localhost
sudo ufw allow from 127.0.0.1 to any port 3306
```

### 2. SSL Certificate

```bash
# Using Let's Encrypt with Certbot
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d your-api-domain.com
```

### 3. Security Headers

Add these headers to your web server configuration:

```
X-Frame-Options: DENY
X-XSS-Protection: 1; mode=block
X-Content-Type-Options: nosniff
Strict-Transport-Security: max-age=31536000; includeSubDomains
Content-Security-Policy: default-src 'self'
```

## Monitoring and Logging

### 1. Log Configuration

```bash
# Set up log rotation
sudo nano /etc/logrotate.d/sweat24

# Add this content:
/var/www/sweat24-laravel-backend/storage/logs/*.log {
    daily
    missingok
    rotate 30
    compress
    notifempty
    create 644 www-data www-data
}
```

### 2. Health Check Endpoint

The application includes a health check endpoint at `/api/health` for monitoring.

### 3. Performance Monitoring

Consider using tools like:
- New Relic
- Datadog
- Laravel Telescope (for development)

## Backup Strategy

### 1. Database Backup

```bash
# Create daily backup script
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
mysqldump -u sweat24_user -p sweat24 > /backups/sweat24_$DATE.sql
# Keep only last 30 days
find /backups -name "sweat24_*.sql" -mtime +30 -delete
```

### 2. File Backup

```bash
# Backup uploaded files and storage
tar -czf /backups/sweat24_files_$DATE.tar.gz /var/www/sweat24-laravel-backend/storage/app
```

## Updates and Maintenance

### 1. Zero-Downtime Deployment

```bash
# Create deployment script
#!/bin/bash
cd /var/www/sweat24-laravel-backend

# Put application in maintenance mode
php artisan down

# Pull latest changes
git pull origin main

# Update dependencies
composer install --optimize-autoloader --no-dev

# Run migrations
php artisan migrate --force

# Clear and cache config
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Bring application back online
php artisan up
```

### 2. Health Checks

```bash
# Check application status
curl -f https://your-api-domain.com/api/health || exit 1

# Check database connectivity
php artisan tinker --execute="DB::connection()->getPdo();"
```

## Troubleshooting

### Common Issues

1. **500 Internal Server Error**
   - Check file permissions
   - Verify environment configuration
   - Check error logs

2. **Database Connection Issues**
   - Verify database credentials
   - Check MySQL service status
   - Confirm firewall rules

3. **CORS Issues**
   - Update `config/cors.php`
   - Verify `SANCTUM_STATEFUL_DOMAINS`
   - Check frontend domain configuration

### Debug Commands

```bash
# Check configuration
php artisan config:show

# Clear all caches
php artisan optimize:clear

# Check routes
php artisan route:list

# Test database connection
php artisan tinker --execute="DB::connection()->getPdo();"
```

## Performance Optimization

### 1. OPCache Configuration

```ini
; /etc/php/8.2/fpm/conf.d/10-opcache.ini
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=4000
opcache.revalidate_freq=2
opcache.fast_shutdown=1
```

### 2. Database Optimization

```sql
-- Add indexes for performance
CREATE INDEX idx_bookings_user_date ON bookings(user_id, booking_date);
CREATE INDEX idx_classes_date_status ON gym_classes(date, status);
CREATE INDEX idx_packages_active ON packages(is_active);
```

### 3. Caching Strategy

```bash
# Enable Redis for better caching
sudo apt install redis-server

# Update .env
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

## Contact

For deployment support or issues, contact the development team.