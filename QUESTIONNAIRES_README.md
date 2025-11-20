# Ερωτηματολόγια Συστήματος - Implementation Guide

## 📋 Επισκόπηση

Το σύστημα ερωτηματολογίων επιτρέπει τη δημιουργία έξυπνων ερωτηματολογίων που ενεργοποιούνται αυτόματα με βάση συγκεκριμένα triggers (εκδηλώσεις) και συλλέγουν δεδομένα από τους χρήστες.

## 🏗️ Αρχιτεκτονική

### Database Schema

#### `questionnaires` Table
```sql
- id (Primary Key)
- title (String)
- description (Text, nullable)
- triggers (JSON Array) - π.χ. ["daily", "after_lesson"]
- frequency_settings (JSON Object, nullable) - για χρονικά triggers
- questions (JSON Array) - ορισμός ερωτήσεων
- is_active (Boolean, default: true)
- created_by (Foreign Key to users)
- timestamps
```

#### `questionnaire_responses` Table
```sql
- id (Primary Key)
- questionnaire_id (Foreign Key)
- user_id (Foreign Key)
- responses (JSON Array) - απαντήσεις χρήστη
- trigger_type (String) - τύπος trigger που ενεργοποίησε
- completed_at (Timestamp)
- session_id (String, nullable) - για tracking sessions
- timestamps
```

### Models

#### Questionnaire Model
```php
class Questionnaire extends Model
{
    protected $fillable = [
        'title', 'description', 'triggers', 'frequency_settings',
        'questions', 'is_active', 'created_by'
    ];

    protected $casts = [
        'triggers' => 'array',
        'frequency_settings' => 'array',
        'questions' => 'array',
        'is_active' => 'boolean'
    ];

    // Relationships
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function responses() { return $this->hasMany(QuestionnaireResponse::class); }

    // Scopes
    public function scopeActive($query) { return $query->where('is_active', true); }
    public function scopeByTrigger($query, $trigger) {
        return $query->whereJsonContains('triggers', $trigger);
    }
}
```

#### QuestionnaireResponse Model
```php
class QuestionnaireResponse extends Model
{
    protected $fillable = [
        'questionnaire_id', 'user_id', 'responses',
        'trigger_type', 'completed_at', 'session_id'
    ];

    protected $casts = [
        'responses' => 'array',
        'completed_at' => 'datetime'
    ];

    // Relationships
    public function questionnaire() { return $this->belongsTo(Questionnaire::class); }
    public function user() { return $this->belongsTo(User::class); }

    // Scopes
    public function scopeByTriggerType($query, $trigger) {
        return $query->where('trigger_type', $trigger);
    }
    public function scopeCompleted($query) { return $query->whereNotNull('completed_at'); }
}
```

## 🎯 Trigger System

### Διαθέσιμοι Triggers

1. **daily** - Καθημερινά ερωτηματολόγια
2. **weekly** - Εβδομαδιαία ερωτηματολόγια
3. **monthly** - Μηνιαία ερωτηματολόγια
4. **after_lesson** - Μετά από ολοκλήρωση μαθήματος
5. **after_purchase** - Μετά από αγορά πακέτου
6. **after_registration** - Μετά από εγγραφή νέου χρήστη

### Frequency Settings

Για χρονικά triggers, μπορείτε να ορίσετε:
```json
{
  "time": "09:00",           // Ώρα αποστολής
  "days": ["monday", "wednesday", "friday"]  // Ημέρες (για weekly)
}
```

## 📝 Question Types

### Υποστηριζόμενοι Τύποι Ερωτήσεων

1. **rating** - Αξιολόγηση 1-5 αστέρια
2. **rating_10** - Αξιολόγηση 1-10
3. **number** - Αριθμητική είσοδος με min/max
4. **text** - Κείμενο ελεύθερης μορφής
5. **multiple_choice** - Πολλαπλή επιλογή
6. **yes_no** - Ναι/Όχι ερώτηση

### Δομή Ερώτησης
```json
{
  "type": "rating",
  "question": "Πώς νιώθετε σήμερα;",
  "description": "Αξιολογήστε τη διάθεσή σας",
  "required": true,
  "options": ["1", "2", "3", "4", "5"],
  "min": null,     // για number type
  "max": null      // για number type
}
```

## 🔧 API Controllers

### QuestionnaireController
- **index()** - Λίστα ερωτηματολογίων (admin only)
- **store()** - Δημιουργία νέου ερωτηματολογίου
- **show()** - Λεπτομέρειες ερωτηματολογίου
- **update()** - Ενημέρωση ερωτηματολογίου
- **destroy()** - Διαγραφή ερωτηματολογίου
- **active()** - Ενεργά ερωτηματολόγια για χρήστη
- **toggleActive()** - Ενεργοποίηση/απενεργοποίηση
- **getStatistics()** - Στατιστικά ερωτηματολογίου
- **getResponses()** - Αποκρίσεις ερωτηματολογίου

### QuestionnaireResponseController
- **index()** - Λίστα αποκρίσεων (admin only)
- **store()** - Υποβολή νέας απόκρισης
- **show()** - Λεπτομέρειες απόκρισης
- **update()** - Ενημέρωση απόκρισης
- **destroy()** - Διαγραφή απόκρισης
- **getByQuestionnaire()** - Αποκρίσεις ανά ερωτηματολόγιο
- **getUserResponses()** - Αποκρίσεις χρήστη
- **getStatistics()** - Στατιστικά αποκρίσεων

## 🛡️ Authorization & Authentication

### Middleware
- **auth:sanctum** - Απαιτεί έγκυρο Bearer token
- **role:admin,trainer** - Για admin endpoints (δημιουργία/διαχείριση)
- **auth:sanctum** - Για user endpoints (υποβολή αποκρίσεων)

### Permissions
```php
// Στον QuestionnaireController
public function canManageQuestionnaires(User $user): bool
{
    return $user->isAdmin() || $user->isTrainer();
}
```

## 🚀 Deployment & Setup

### Automated Setup
```bash
# Εκτέλεση deployment script
./setup_questionnaire_system.sh
```

### Manual Setup
```bash
# 1. Run migrations
php artisan migrate

# 2. Create sample data
php artisan db:seed --class=QuestionnaireSeeder

# 3. Clear cache
php artisan config:clear && php artisan route:clear && php artisan config:cache
```

## 📊 Sample Data

### Εβδομαδιαία Αξιολόγηση
- **Triggers**: weekly
- **Questions**: Rating, Text, Rating 1-10, Number, Yes/No
- **Purpose**: Συνολική αξιολόγηση εβδομάδας

### Αξιολόγηση Μαθήματος
- **Triggers**: after_lesson
- **Questions**: Rating x2, Multiple Choice, Text
- **Purpose**: Feedback μετά από μάθημα

### Καθημερινή Ευεξία
- **Triggers**: daily
- **Questions**: Rating, Rating, Yes/No, Text
- **Purpose**: Παρακολούθηση καθημερινής διάθεσης

## 🧪 Testing

### Unit Tests
```php
// tests/Unit/QuestionnaireTest.php
public function test_questionnaire_creation()
{
    $user = User::factory()->create(['role' => 'admin']);

    $questionnaire = Questionnaire::factory()->create([
        'created_by' => $user->id
    ]);

    $this->assertDatabaseHas('questionnaires', [
        'id' => $questionnaire->id
    ]);
}
```

### Feature Tests
```php
// tests/Feature/QuestionnaireApiTest.php
public function test_admin_can_create_questionnaire()
{
    $admin = User::factory()->create(['role' => 'admin']);
    $token = $admin->createToken('test')->plainTextToken;

    $response = $this->withHeaders([
        'Authorization' => "Bearer {$token}"
    ])->postJson('/api/v1/questionnaires', [
        'title' => 'Test Questionnaire',
        'questions' => [/* ... */]
    ]);

    $response->assertStatus(201);
}
```

## 📈 Analytics & Statistics

### Questionnaire Statistics
- Συνολικές αποκρίσεις
- Ποσοστό ολοκλήρωσης
- Μέση βαθμολογία
- Trends ανά χρονική περίοδο

### Response Analytics
- Completion rates ανά trigger type
- Average response time
- Question-level analytics
- User engagement metrics

## 🔄 Future Enhancements

### Potential Features
1. **Automated Triggers** - Queue jobs για αυτόματη αποστολή
2. **Conditional Questions** - Ερωτήσεις βασισμένες σε προηγούμενες απαντήσεις
3. **Advanced Analytics** - Charts και reports
4. **Push Notifications** - Ειδοποιήσεις για νέα ερωτηματολόγια
5. **Template System** - Προκατασκευασμένα templates
6. **Export Functionality** - CSV/PDF exports

### Queue Jobs για Triggers
```php
// app/Jobs/SendQuestionnaireNotifications.php
class SendQuestionnaireNotifications implements ShouldQueue
{
    public function handle()
    {
        // Find users who should receive questionnaires
        // Send push notifications
        // Log delivery
    }
}
```

## 🐛 Troubleshooting

### Common Issues

#### 1. Routes not found
```bash
php artisan route:list | grep questionnaire
# Check if routes are registered
```

#### 2. Authorization errors
```php
// Check user role
$user = User::find(1);
dd($user->role); // Should be 'admin' or 'trainer'
```

#### 3. Validation errors
```php
// Check request data structure
dd($request->all());
```

#### 4. Database issues
```bash
# Check migrations
php artisan migrate:status

# Reset if needed
php artisan migrate:fresh --seed
```

## 📞 Support

Για οποιαδήποτε προβλήματα ή ερωτήσεις:
1. Ελέγξτε τα logs: `storage/logs/laravel.log`
2. Δοκιμάστε με Postman collection
3. Επικοινωνήστε με το development team

---

**Version:** 1.1
**Date:** September 24, 2025
**Status:** Production Ready
