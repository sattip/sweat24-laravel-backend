# CORS Configuration Fix for Production

## Problem
The React admin panel cannot authenticate with the Laravel backend due to CORS restrictions.

## Solution

### 1. Update Production `.env` file

Add your admin panel domain to the CORS allowed origins:

```env
# If your admin panel is at https://admin.sweat24.gr
CORS_ALLOWED_ORIGINS=https://admin.sweat24.gr,http://localhost:5173,http://localhost:5174

# Or allow all origins (NOT recommended for production)
CORS_ALLOWED_ORIGINS=*
```

### 2. Update `config/cors.php`

Make sure your `config/cors.php` file has:

```php
return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    
    'allowed_methods' => ['*'],
    
    'allowed_origins' => explode(',', env('CORS_ALLOWED_ORIGINS', '')),
    
    'allowed_origins_patterns' => [],
    
    'allowed_headers' => ['*'],
    
    'exposed_headers' => [],
    
    'max_age' => 0,
    
    'supports_credentials' => true,
];
```

### 3. Clear Configuration Cache

After updating the `.env` file, run:

```bash
php artisan config:cache
php artisan route:cache
```

### 4. Verify Nginx Configuration (if using Nginx)

Add these headers to your Nginx server block:

```nginx
location / {
    if ($request_method = 'OPTIONS') {
        add_header 'Access-Control-Allow-Origin' '$http_origin' always;
        add_header 'Access-Control-Allow-Methods' 'GET, POST, PUT, DELETE, OPTIONS' always;
        add_header 'Access-Control-Allow-Headers' 'DNT,User-Agent,X-Requested-With,If-Modified-Since,Cache-Control,Content-Type,Range,Authorization' always;
        add_header 'Access-Control-Max-Age' 1728000;
        add_header 'Content-Type' 'text/plain; charset=utf-8';
        add_header 'Content-Length' 0;
        return 204;
    }
}
```

### 5. Test CORS Headers

Test if CORS headers are being sent:

```bash
curl -I -X OPTIONS https://sweat93laravel.obs.com.gr/api/v1/auth/login \
  -H "Origin: https://your-admin-domain.com" \
  -H "Access-Control-Request-Method: POST" \
  -H "Access-Control-Request-Headers: Content-Type"
```

## Quick Fix for Testing

If you need a quick fix for testing, you can temporarily update your production `.env`:

```env
CORS_ALLOWED_ORIGINS=*
```

**Note**: This allows all origins and should NOT be used in production permanently.

## Debugging

1. Check browser console for the exact origin making the request
2. Ensure that origin is included in `CORS_ALLOWED_ORIGINS`
3. Check Laravel logs for any CORS-related errors
4. Verify the backend is returning proper CORS headers

## Common Issues

1. **Trailing slashes**: Make sure origins don't have trailing slashes
   - ✅ `https://admin.sweat24.gr`
   - ❌ `https://admin.sweat24.gr/`

2. **Protocol mismatch**: Include both http and https if needed
   - `CORS_ALLOWED_ORIGINS=http://localhost:5173,https://admin.sweat24.gr`

3. **Port numbers**: Include port numbers for development
   - `http://localhost:5173` not just `http://localhost`