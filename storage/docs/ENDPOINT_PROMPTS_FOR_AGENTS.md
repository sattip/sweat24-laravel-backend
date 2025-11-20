# 🎯 ENDPOINT PROMPTS FOR AGENTS

## 📱 **CLIENT APP AGENT - Εξειδικευμένες Υπηρεσίες (Αιτήματα Ραντεβού)**

### **Context:**
Το Client App πρέπει να υποστηρίξει την υποβολή αιτημάτων ραντεβού για EMS και Personal Training μέσω της ενότητας "Εξειδικευμένες Υπηρεσίες".

### **Endpoints για Client App:**

#### **1. Υποβολή Νέου Αιτήματος**
```
POST https://sweat93laravel.obs.com.gr/api/v1/booking-requests

Headers:
{
  "Content-Type": "application/json",
  "Accept": "application/json"
}

Body:
{
  "service_type": "ems", // ή "personal"
  "instructor_id": null, // προαιρετικό
  "client_name": "Όνομα Πελάτη",
  "client_email": "email@example.com",
  "client_phone": "+30 210 1234567",
  "preferred_time_slots": [
    {
      "date": "2025-07-25",
      "start_time": "14:00",
      "end_time": "15:00"
    },
    {
      "date": "2025-07-26", 
      "start_time": "10:00",
      "end_time": "11:00"
    }
  ],
  "notes": "Προαιρετικές σημειώσεις"
}

Expected Response (201):
{
  "message": "Booking request submitted successfully",
  "data": {
    "id": 6,
    "service_type": "ems",
    "client_name": "Όνομα Πελάτη",
    "status": "pending",
    "preferred_time_slots": [...],
    "created_at": "2025-07-22T16:30:00.000000Z"
  }
}
```

#### **2. Προβολή Αιτημάτων Χρήστη**
```
GET https://sweat93laravel.obs.com.gr/api/v1/booking-requests/my-requests

Headers:
{
  "Authorization": "Bearer {USER_TOKEN}",
  "Accept": "application/json"
}

Expected Response (200):
{
  "data": [
    {
      "id": 6,
      "service_type": "ems",
      "client_name": "Όνομα Πελάτη",
      "status": "pending", // pending, confirmed, rejected, cancelled
      "preferred_time_slots": [...],
      "confirmed_date": null,
      "confirmed_time": null,
      "created_at": "2025-07-22T16:30:00.000000Z"
    }
  ],
  "meta": {
    "total": 1,
    "per_page": 10,
    "current_page": 1
  }
}
```

#### **3. Ακύρωση Αιτήματος**
```
POST https://sweat93laravel.obs.com.gr/api/v1/booking-requests/{id}/cancel

Headers:
{
  "Authorization": "Bearer {USER_TOKEN}",
  "Content-Type": "application/json"
}

Body:
{
  "reason": "Λόγος ακύρωσης (προαιρετικό)"
}

Expected Response (200):
{
  "message": "Booking request cancelled successfully",
  "data": { ... }
}
```

#### **4. Διαθέσιμοι Εκπαιδευτές**
```
GET https://sweat93laravel.obs.com.gr/api/v1/booking-requests/instructors?service_type=ems

Expected Response (200):
[
  {
    "id": 1,
    "name": "Όνομα Εκπαιδευτή",
    "specialties": ["EMS", "Personal Training"],
    "image_url": "..."
  }
]
```

---

## 🏢 **ADMIN PANEL AGENT - Διαχείριση Αιτημάτων Ραντεβού**

### **Context:**
Το Admin Panel πρέπει να παρέχει στη διοίκηση του γυμναστηρίου τη δυνατότητα διαχείρισης αιτημάτων ραντεβού στην ενότητα "Εξειδικευμένες Υπηρεσίες" → Tab "Αιτήματα Ραντεβού".

### **Endpoints για Admin Panel:**

#### **1. Λίστα Όλων των Αιτημάτων**
```
GET https://sweat93laravel.obs.com.gr/api/v1/admin/booking-requests

Headers:
{
  "Authorization": "Bearer {ADMIN_TOKEN}",
  "Accept": "application/json"
}

Query Parameters (προαιρετικά):
- status: pending, confirmed, rejected, cancelled, completed
- service_type: ems, personal  
- from_date: 2025-07-20
- to_date: 2025-07-25

Expected Response (200):
{
  "data": [
    {
      "id": 6,
      "user_id": null,
      "service_type": "ems",
      "instructor_id": null,
      "client_name": "Όνομα Πελάτη",
      "client_email": "email@example.com",
      "client_phone": "+30 210 1234567",
      "preferred_time_slots": [
        {
          "date": "2025-07-25",
          "start_time": "14:00", 
          "end_time": "15:00"
        }
      ],
      "notes": "Σημειώσεις πελάτη",
      "status": "pending",
      "admin_notes": null,
      "confirmed_date": null,
      "confirmed_time": null,
      "rejection_reason": null,
      "processed_by": null,
      "processed_at": null,
      "created_at": "2025-07-22T16:30:00.000000Z",
      "user": null,
      "instructor": null,
      "processedBy": null
    }
  ],
  "meta": {
    "total": 5,
    "per_page": 20,
    "current_page": 1
  }
}
```

#### **2. Επιβεβαίωση Αιτήματος**
```
POST https://sweat93laravel.obs.com.gr/api/v1/admin/booking-requests/{id}/confirm

Headers:
{
  "Authorization": "Bearer {ADMIN_TOKEN}",
  "Content-Type": "application/json"
}

Body:
{
  "confirmed_date": "2025-07-25",
  "confirmed_time": "14:00",
  "instructor_id": 1, // προαιρετικό
  "admin_notes": "Επιβεβαίωση ραντεβού για EMS"
}

Expected Response (200):
{
  "message": "Booking request confirmed successfully",
  "data": {
    "id": 6,
    "status": "confirmed",
    "confirmed_date": "2025-07-25",
    "confirmed_time": "14:00",
    "processed_by": 1,
    "processed_at": "2025-07-22T16:35:00.000000Z",
    ...
  }
}
```

#### **3. Απόρριψη Αιτήματος**
```
POST https://sweat93laravel.obs.com.gr/api/v1/admin/booking-requests/{id}/reject

Headers:
{
  "Authorization": "Bearer {ADMIN_TOKEN}",
  "Content-Type": "application/json"
}

Body:
{
  "rejection_reason": "Δεν υπάρχει διαθεσιμότητα για τις προτεινόμενες ημέρες",
  "admin_notes": "Προτείνεται επανυποβολή με άλλες ημέρες"
}

Expected Response (200):
{
  "message": "Booking request rejected",
  "data": {
    "id": 6,
    "status": "rejected",
    "rejection_reason": "...",
    "processed_by": 1,
    "processed_at": "2025-07-22T16:35:00.000000Z",
    ...
  }
}
```

#### **4. Ολοκλήρωση Ραντεβού**
```
POST https://sweat93laravel.obs.com.gr/api/v1/admin/booking-requests/{id}/complete

Headers:
{
  "Authorization": "Bearer {ADMIN_TOKEN}",
  "Accept": "application/json"
}

Expected Response (200):
{
  "message": "Booking marked as completed",
  "data": {
    "id": 6,
    "status": "completed",
    ...
  }
}
```

#### **5. Στατιστικά Dashboard**
```
GET https://sweat93laravel.obs.com.gr/api/v1/admin/booking-requests/statistics

Headers:
{
  "Authorization": "Bearer {ADMIN_TOKEN}",
  "Accept": "application/json"
}

Expected Response (200):
{
  "total": 5,
  "pending": 3,
  "confirmed": 1,
  "rejected": 1,
  "completed": 0,
  "by_service": {
    "ems": 4,
    "personal": 1
  },
  "recent_requests": [
    {
      "id": 6,
      "client_name": "...",
      "service_type": "ems",
      "status": "pending",
      "created_at": "..."
    }
  ]
}
```

---

## 🔐 **AUTHENTICATION NOTES**

### **Client App:**
- **POST /booking-requests:** Public (χωρίς auth) - για guest users
- **GET /my-requests:** Requires Bearer token από authenticated user
- **POST /cancel:** Requires Bearer token από authenticated user

### **Admin Panel:**
- **Όλα τα /admin/booking-requests endpoints:** Require Bearer token από admin user
- **Admin Role Check:** Middleware επαληθεύει ότι ο user έχει admin δικαιώματα

---

## 🎯 **IMPLEMENTATION NOTES**

### **Service Types:**
- `"ems"` - EMS Training
- `"personal"` - Personal Training

### **Status Flow:**
1. `"pending"` - Αρχική κατάσταση μετά υποβολή
2. `"confirmed"` - Επιβεβαιωμένο από admin
3. `"rejected"` - Απορριφθέν από admin
4. `"cancelled"` - Ακυρωμένο από χρήστη
5. `"completed"` - Ολοκληρωμένο ραντεβού

### **Validation Rules:**
- `preferred_time_slots`: Minimum 1, Maximum 3 slots
- `date`: Must be after today
- `start_time/end_time`: Format HH:MM
- `end_time`: Must be after start_time 