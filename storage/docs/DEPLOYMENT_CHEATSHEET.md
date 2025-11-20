# ⚡ DEPLOYMENT CHEAT SHEET

## 🚀 **QUICK DEPLOY (5 COMMANDS)**

```bash
git add . && git commit -m "Your message here" && git push origin main
php artisan migrate --force
php artisan config:clear && php artisan route:clear && php artisan cache:clear
curl "https://sweat93laravel.obs.com.gr/api/v1/dashboard/stats" | head -5
echo "✅ DEPLOYMENT COMPLETE!"
```

## 🔥 **EMERGENCY COMMANDS**

```bash
# Fix broken deployment
php artisan config:clear && php artisan route:clear && php artisan view:clear && php artisan cache:clear

# Database check
php artisan tinker --execute="echo 'Users: ' . \App\Models\User::count() . ', Bookings: ' . \App\Models\Booking::count();"

# API test
curl "https://sweat93laravel.obs.com.gr/api/v1/dashboard/stats"

# Rollback (DANGER!)
git log --oneline -3 && git reset --hard COMMIT_HASH && git push --force origin main
```

## 📋 **PROJECT INFO**

- **URL:** `https://sweat93laravel.obs.com.gr/`
- **Path:** `/home/forge/sweat93laravel.obs.com.gr`
- **DB:** SQLite (not MySQL)
- **Always use:** `--force` για migrations

## 🎯 **SUCCESS CRITERIA**

✅ Git push works  
✅ No migration errors  
✅ API responds  
✅ Database has data  
✅ Caches cleared 