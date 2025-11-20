# Ερωτηματολόγια - API Documentation

## 📋 Overview

Το σύστημα ερωτηματολογίων επιτρέπει τη δημιουργία, διαχείριση και υποβολή ερωτηματολογίων με διάφορους τύπους ερωτήσεων.

## 🔐 Authentication

Όλα τα endpoints απαιτούν Bearer token authentication:
```
Authorization: Bearer {token}
```

## 📚 API Endpoints

### Admin Endpoints

#### 1. Λίστα Ερωτηματολογίων
```
GET /api/v1/questionnaires
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "title": "Εβδομαδιαία Αξιολόγηση",
      "description": "Πώς ήταν η εβδομάδα σας;",
      "triggers": ["weekly"],
      "questions": [...],
      "is_active": true,
      "created_by": 1,
      "created_at": "2025-09-24T10:00:00Z",
      "updated_at": "2025-09-24T10:00:00Z"
    }
  ]
}
```

#### 2. Δημιουργία Ερωτηματολογίου
```
POST /api/v1/questionnaires
```

**Request Body:**
```json
{
  "title": "Καθημερινή Αξιολόγηση",
  "description": "Πώς νιώθετε σήμερα;",
  "triggers": ["daily"],
  "frequency_settings": {
    "time": "09:00"
  },
  "questions": [
    {
      "type": "rating",
      "question": "Πώς είναι τα επίπεδα ενέργειάς σας;",
      "description": "Αξιολογήστε πόσο ενεργητικός νιώθετε",
      "required": true,
      "options": ["1", "2", "3", "4", "5"]
    },
    {
      "type": "text",
      "question": "Σχόλια για σήμερα;",
      "description": "Περιγράψτε την ημέρα σας",
      "required": false
    }
  ],
  "is_active": true
}
```

#### 3. Λεπτομέρειες Ερωτηματολογίου
```
GET /api/v1/questionnaires/{id}
```

#### 4. Ενημέρωση Ερωτηματολογίου
```
PUT /api/v1/questionnaires/{id}
```

#### 5. Διαγραφή Ερωτηματολογίου
```
DELETE /api/v1/questionnaires/{id}
```

#### 6. Ενεργοποίηση/Απενεργοποίηση
```
POST /api/v1/questionnaires/{id}/toggle-active
```

#### 7. Στατιστικά Ερωτηματολογίου
```
GET /api/v1/questionnaires/{id}/statistics
```

**Response:**
```json
{
  "success": true,
  "data": {
    "total_responses": 45,
    "completion_rate": 89.2,
    "average_rating": 4.1,
    "response_trends": [...]
  }
}
```

#### 8. Αποκρίσεις Ερωτηματολογίου
```
GET /api/v1/questionnaires/{id}/responses
```

### User Endpoints

#### 1. Ενεργά Ερωτηματολόγια
```
GET /api/v1/questionnaires/active?user_id={user_id}
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "title": "Καθημερινή Ευεξία",
      "description": "Πώς νιώθετε σήμερα;",
      "questions": [
        {
          "type": "rating",
          "question": "Πώς είναι τα επίπεδα ενέργειάς σας;",
          "description": "Αξιολογήστε πόσο ενεργητικός νιώθετε",
          "required": true,
          "options": ["1", "2", "3", "4", "5"]
        }
      ],
      "trigger_type": "daily"
    }
  ]
}
```

#### 2. Υποβολή Απόκρισης
```
POST /api/v1/questionnaire-responses
```

**Request Body:**
```json
{
  "questionnaire_id": 1,
  "responses": [
    {
      "question_index": 0,
      "answer": "4"
    },
    {
      "question_index": 1,
      "answer": "Αισθάνομαι πολύ καλά σήμερα!"
    }
  ],
  "trigger_type": "daily",
  "session_id": "optional-session-id"
}
```

#### 3. Αποκρίσεις Χρήστη
```
GET /api/v1/questionnaire-responses/user
```

## 🎯 Question Types

### 1. Rating (1-5)
```json
{
  "type": "rating",
  "question": "Πώς θα βαθμολογούσατε;",
  "description": "Σε κλίμακα 1-5",
  "required": true,
  "options": ["1", "2", "3", "4", "5"]
}
```

### 2. Rating (1-10)
```json
{
  "type": "rating_10",
  "question": "Σε κλίμακα 1-10;",
  "description": "Με 10 να είναι η μέγιστη",
  "required": true,
  "options": ["1", "2", "3", "4", "5", "6", "7", "8", "9", "10"]
}
```

### 3. Number
```json
{
  "type": "number",
  "question": "Πόσα χιλιόμετρα έτρεξες;",
  "description": "Εισάγετε αριθμό",
  "required": false,
  "min": 0,
  "max": 100
}
```

### 4. Text
```json
{
  "type": "text",
  "question": "Σχόλια;",
  "description": "Περιγράψτε λεπτομερώς",
  "required": false
}
```

### 5. Multiple Choice
```json
{
  "type": "multiple_choice",
  "question": "Τι σας άρεσε περισσότερο;",
  "description": "Επιλέξτε όλα όσα ισχύουν",
  "required": false,
  "options": ["Ενταση", "Ποικιλία", "Προπονητής", "Περιβάλλον"]
}
```

### 6. Yes/No
```json
{
  "type": "yes_no",
  "question": "Θα μας προτείνατε;",
  "description": "Η γνώμη σας είναι σημαντική",
  "required": true
}
```

## 🚀 Trigger Types

- `daily` - Καθημερινά
- `weekly` - Εβδομαδιαία
- `monthly` - Μηνιαία
- `after_lesson` - Μετά από μάθημα
- `after_purchase` - Μετά από αγορά
- `after_registration` - Μετά από εγγραφή

## 📊 Response Format

```json
{
  "success": true,
  "data": {
    "id": 1,
    "questionnaire_id": 1,
    "user_id": 123,
    "responses": [
      {
        "question_index": 0,
        "question": "Πώς νιώθετε;",
        "answer": "4"
      },
      {
        "question_index": 1,
        "question": "Σχόλια;",
        "answer": "Πολύ καλά!"
      }
    ],
    "trigger_type": "daily",
    "completed_at": "2025-09-24T10:30:00Z"
  }
}
```

## ⚠️ Error Responses

```json
{
  "success": false,
  "errors": {
    "questionnaire_id": ["The questionnaire id field is required."],
    "responses": ["The responses field is required."]
  }
}
```

```json
{
  "success": false,
  "error": "Unauthorized"
}
```

## 🧪 Testing Examples

### Δημιουργία Test Ερωτηματολογίου
```bash
curl -X POST http://localhost:8000/api/v1/questionnaires \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Test Questionnaire",
    "description": "Testing API",
    "triggers": ["daily"],
    "questions": [
      {
        "type": "rating",
        "question": "How do you feel?",
        "description": "Rate your mood",
        "required": true,
        "options": ["1", "2", "3", "4", "5"]
      }
    ],
    "is_active": true
  }'
```

### Υποβολή Test Απόκρισης
```bash
curl -X POST http://localhost:8000/api/v1/questionnaire-responses \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "questionnaire_id": 1,
    "responses": [
      {
        "question_index": 0,
        "answer": "4"
      }
    ],
    "trigger_type": "daily"
  }'
```

---

**Version:** 1.1
**Updated:** September 24, 2025
**Status:** Production Ready
