# 🚀 SWEAT24 LARAVEL BACKEND - DEPLOYMENT GUIDE

> **Οδηγίες για άλλους AI Agents του project για σωστό deployment**

## 📋 **ΒΑΣΙΚΕΣ ΠΛΗΡΟΦΟΡΙΕΣ PROJECT**

- **Production Domain:** `https://sweat93laravel.obs.com.gr/`
- **Server:** Laravel Forge on DigitalOcean
- **Database:** SQLite (fallback από MySQL λόγω connection issues)
- **Git Repository:** Auto-deployment από main branch
- **Working Directory:** `/home/forge/sweat93laravel.obs.com.gr`

## 🔄 **STANDARD DEPLOYMENT PROCESS**

### **Βήμα 1: Code Changes**
```bash
# Κάνε τις αλλαγές σου στον κώδικα
# Πάντα test τοπικά πριν το deployment
```

### **Βήμα 2: Git Operations**
```bash
# Check τρέχουσα κατάσταση
git status

# Add όλες τις αλλαγές
git add .

# Commit με περιγραφικό μήνυμα
git commit -m "ΠΕΡΙΓΡΑΦΗ: Τι έκανες - π.χ. Add BookingRequest system for EMS/Personal appointments"

# Push στο production
git push origin main
```

### **Βήμα 3: Database Migrations (αν υπάρχουν)**
```bash
# Run migrations με force flag (για production)
php artisan migrate --force

# Αν χρειάζεται seeding (σπάνια)
php artisan db:seed --force
```

### **Βήμα 4: Clear Caches (ΥΠΟΧΡΕΩΤΙΚΟ)**
```bash
# Clear όλα τα caches
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# Optimize για production (προαιρετικό)
php artisan config:cache
php artisan route:cache
```

### **Βήμα 5: Verification**
```bash
# Test ότι το API λειτουργεί
curl "https://sweat93laravel.obs.com.gr/api/v1/dashboard/stats" | head -10

# Check τελευταίο commit
git log --oneline -1
```

## ⚠️ **ΣΥΧΝΑ ΛΑΘΗ & ΛΥΣΕΙΣ**

### **Λάθος 1: Migration Prompt**
```
❌ ΠΡΟΒΛΗΜΑ: "Are you sure you want to run this command?" dialog
✅ ΛΥΣΗ: Πάντα χρησιμοποιείς --force flag
```

### **Λάθος 2: Database Connection Errors**
```bash
❌ ΠΡΟΒΛΗΜΑ: "Connection refused" ή MySQL errors
✅ ΛΥΣΗ: Το project χρησιμοποιεί SQLite
# Αν δεις MySQL errors, τρέξε:
php artisan config:clear
```

### **Λάθος 3: Middleware/Auth Errors**
```bash
❌ ΠΡΟΒΛΗΜΑ: "Unauthenticated" ή middleware issues
✅ ΛΥΣΗ: Clear route cache
php artisan route:clear
```

## 🏗️ **ΟΔΗΓΙΕΣ ΓΙΑ ΝΕΑ FEATURES**

### **Νέο Model/Migration:**
```bash
# 1. Δημιούργησε migration
php artisan make:migration create_table_name

# 2. Δημιούργησε model
php artisan make:model ModelName

# 3. Μετά το coding, τρέξε deployment process
```

### **Νέο Controller:**
```bash
# 1. Δημιούργησε controller
php artisan make:controller ControllerName

# 2. Πρόσθεσε routes στο routes/api.php
# 3. Τρέξε deployment process
```

### **Νέο API Endpoint:**
```bash
# 1. Πρόσθεσε route στο routes/api.php
# 2. Υλοποίησε method στον controller
# 3. Test τοπικά
# 4. Deployment process
```

## 🔐 **AUTHENTICATION & MIDDLEWARE**

### **Public Routes (χωρίς auth):**
```php
// Στο routes/api.php - μέσα στο v1 group χωρίς middleware
Route::post('booking-requests', [BookingRequestController::class, 'store']);
```

### **Authenticated Routes:**
```php
// Μέσα στο middleware('auth:sanctum') group
Route::get('booking-requests/my-requests', [BookingRequestController::class, 'userRequests']);
```

### **Admin Routes:**
```php
// Μέσα στο middleware(['role:admin']) group
Route::get('admin/booking-requests', [BookingRequestController::class, 'index']);
```

## 📊 **DATABASE OPERATIONS**

### **Για νέα migration:**
```bash
# 1. Create migration
php artisan make:migration description_of_change

# 2. Edit migration file στο database/migrations/
# 3. Run deployment process
# 4. Migration θα τρέξει αυτόματα με --force
```

### **Για Model relationships:**
```php
// Πάντα πρόσθεσε relationships και στα δύο models
// User.php
public function bookingRequests() {
    return $this->hasMany(BookingRequest::class);
}

// BookingRequest.php  
public function user() {
    return $this->belongsTo(User::class);
}
```

## 🧪 **TESTING & VERIFICATION**

### **Quick API Tests:**
```bash
# Test public endpoint
curl "https://sweat93laravel.obs.com.gr/api/v1/dashboard/stats"

# Check database counts
php artisan tinker --execute="echo 'Total users: ' . \App\Models\User::count();"
```

### **Frontend Integration Checks:**
```bash
# Admin Panel endpoints need:
# - Bearer token authentication
# - Admin role verification

# Client App endpoints need:
# - Bearer token για authenticated routes
# - Public access για guest routes
```

## 🚨 **EMERGENCY PROCEDURES**

### **Rollback αν κάτι πάει στραβά:**
```bash
# 1. Check τελευταία commits
git log --oneline -5

# 2. Rollback στο προηγούμενο working commit
git reset --hard COMMIT_HASH

# 3. Force push (ΠΡΟΣΟΧΗ!)
git push --force origin main

# 4. Clear caches
php artisan config:clear && php artisan route:clear
```

### **Database corruption:**
```bash
# Restore από backup ή recreate tables
php artisan migrate:fresh --force
php artisan db:seed --force
```

## 📝 **DOCUMENTATION REQUIREMENTS**

### **Μετά από κάθε νέο feature:**
1. **Update API documentation** στα comments
2. **Δημιούργησε completion report** για τον user
3. **Test όλα τα related endpoints**
4. **Verify frontend compatibility**

### **Completion Report Format:**
```text
=== COMPLETION REPORT ===

✅ IMPLEMENTED:
- Feature description
- New endpoints with methods
- Database changes

📊 API ENDPOINTS:
- GET /api/v1/endpoint - Description
- POST /api/v1/endpoint - Description

🗄️ DATABASE CHANGES:
- New table: table_name
- New fields: field1, field2

🧪 TESTING:
- ✅ Endpoint works
- ✅ Database stores correctly
- ✅ Frontend compatible
```

## 🔧 **TROUBLESHOOTING COMMANDS**

```bash
# Check Laravel status
php artisan about

# Check routes
php artisan route:list | grep booking

# Check database
php artisan tinker --execute="DB::select('SELECT COUNT(*) as count FROM sqlite_master');"

# Check logs
tail -f storage/logs/laravel.log

# Restart services (if needed)
sudo service nginx restart
sudo service php8.1-fpm restart
```

---

## ⭐ **SUCCESS CHECKLIST**

Πριν θεωρήσεις το deployment complete:

- [ ] ✅ Git push επιτυχής
- [ ] ✅ Migrations έτρεξαν χωρίς error  
- [ ] ✅ Caches cleared
- [ ] ✅ API endpoints απαντούν
- [ ] ✅ Database έχει τα νέα δεδομένα
- [ ] ✅ Frontend apps μπορούν να κάνουν connect
- [ ] ✅ Completion report έτοιμο

**🎯 Follow αυτήν την διαδικασία κάθε φορά και θα έχεις 100% επιτυχία deployment!** 