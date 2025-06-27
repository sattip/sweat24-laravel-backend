# Production CORS Configuration Update

## Your Setup
- **Laravel Backend API**: https://sweat93laravel.obs.com.gr
- **React Admin Panel**: https://sweat24backend.obs.com.gr

## Steps to Fix CORS

### 1. SSH into your production server hosting the Laravel backend

### 2. Edit the `.env` file

Add this line to your production `.env` file:

```bash
CORS_ALLOWED_ORIGINS=https://sweat24backend.obs.com.gr,http://localhost:5173,http://localhost:5174
```

### 3. Pull the latest code (if not already done)

```bash
cd /path/to/your/laravel/app
git pull origin main
```

### 4. Clear all caches

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan config:cache
```

### 5. Restart services

```bash
# If using PHP-FPM
sudo service php8.2-fpm restart

# If using Nginx
sudo service nginx restart

# Or if using Apache
sudo service apache2 restart
```

## Verify CORS Headers

After making these changes, test if CORS is working:

```bash
curl -I -X OPTIONS https://sweat93laravel.obs.com.gr/api/v1/auth/login \
  -H "Origin: https://sweat24backend.obs.com.gr" \
  -H "Access-Control-Request-Method: POST" \
  -H "Access-Control-Request-Headers: Content-Type"
```

You should see headers like:
- `Access-Control-Allow-Origin: https://sweat24backend.obs.com.gr`
- `Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS`
- `Access-Control-Allow-Headers: *`

## Alternative: Laravel Forge

If you're using Laravel Forge, you can:

1. Go to your site's Environment tab
2. Add: `CORS_ALLOWED_ORIGINS=https://sweat24backend.obs.com.gr,http://localhost:5173,http://localhost:5174`
3. Click "Save"
4. Then click "Restart" to restart the services

## Test the Login

After updating, your React admin panel at https://sweat24backend.obs.com.gr should be able to login successfully with:
- Email: `admin@sweat24.gr`
- Password: `password`

## Troubleshooting

If it still doesn't work:

1. Check the browser console for the exact error
2. Check Laravel logs: `tail -f storage/logs/laravel.log`
3. Verify the origin in browser DevTools Network tab
4. Make sure there's no trailing slash in the CORS origin

## Security Note

Once everything is working, consider:
1. Removing `http://localhost:5173,http://localhost:5174` from production
2. Only keeping: `CORS_ALLOWED_ORIGINS=https://sweat24backend.obs.com.gr`