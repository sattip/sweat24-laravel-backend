# 🎯 Admin API Guide - Sweat93 Gym Management System

## 📋 Overview

This guide provides comprehensive instructions for administrators to manage the Sweat93 Gym Management System through the API. The system includes complete CRUD operations for Services, Trial Appointments, Appointment Requests, and all related entities.

---

## 🔐 Authentication

All admin operations require authentication with a Bearer token:

```bash
Authorization: Bearer YOUR_ADMIN_TOKEN
```

**Note:** Admin access is required for most operations. Make sure your user has `role: 'admin'` in the system.

---

## 🏷️ Services Management

### 📋 List All Services
```bash
GET /api/v1/services?page=1&per_page=15&active_only=true&sort_by=display_order
```

**Query Parameters:**
- `page`: Page number (default: 1)
- `per_page`: Items per page (default: 15, max: 100)
- `active_only`: Show only active services (default: false)
- `sort_by`: Sort field (name, display_order, created_at)
- `sort_direction`: Sort direction (asc, desc)

**Example Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "SEMI PERSONAL",
      "slug": "semi-personal",
      "trial_price": 1500,
      "is_active": true,
      "display_order": 1,
      "allows_trial": true,
      "max_trial_per_user": 1
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 6,
    "last_page": 1
  }
}
```

### ➕ Create New Service
```bash
POST /api/v1/services
Content-Type: application/json
Authorization: Bearer YOUR_ADMIN_TOKEN
```

**Request Body:**
```json
{
  "name": "NEW SERVICE NAME",
  "slug": "new-service-slug",
  "description": "Detailed description of the service",
  "icon": "service-icon",
  "trial_price": 2500,
  "is_active": true,
  "display_order": 10,
  "allows_trial": true,
  "max_trial_per_user": 1
}
```

---

## 📦 Packages Management

### 📋 List All Packages
```bash
GET /api/v1/packages?page=1&per_page=15
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Premium Package",
      "price": 25000,
      "sessions": 10,
      "duration": 30,
      "services": [
        {
          "id": 1,
          "name": "PERSONAL TRAINING",
          "slug": "personal-training",
          "trial_price": 2500,
          "is_active": true,
          "display_order": 1,
          "allows_trial": true,
          "max_trial_per_user": 1
        },
        {
          "id": 2,
          "name": "PILATES PERSONAL",
          "slug": "pilates-personal",
          "trial_price": 2000,
          "is_active": true,
          "display_order": 2,
          "allows_trial": true,
          "max_trial_per_user": 1
        }
      ],
      "status": "active",
      "description": "Premium personal training package"
    }
  ]
}
```

### ➕ Create Package
```bash
POST /api/v1/packages
Content-Type: application/json
Authorization: Bearer YOUR_ADMIN_TOKEN
```

**Request Body:**
```json
{
  "name": "Premium Package",
  "price": 250.00,
  "sessions": 10,
  "duration": 30,
  "service_ids": [1, 2],
  "status": "active",
  "description": "Premium personal training package"
}
```

**Required Fields:**
- `name` (string): Package name
- `price` (decimal): Package price
- `sessions` (integer): Number of sessions
- `duration` (integer): Duration in days

**Optional Fields:**
- `service_ids` (array): Array of service IDs this package belongs to (supports multiple services)
- `status` (string): active/inactive (default: active)
- `description` (string): Package description

### ✏️ Update Package
```bash
PUT /api/v1/packages/{package_id}
Content-Type: application/json
Authorization: Bearer YOUR_ADMIN_TOKEN
```

**Request Body:**
```json
{
  "name": "Updated Premium Package",
  "price": 275.00,
  "sessions": 12,
  "duration": 30,
  "service_ids": [1, 2, 3],
  "status": "active",
  "description": "Updated premium package with multiple services"
}
```

### 🗑️ Delete Package
```bash
DELETE /api/v1/packages/{package_id}
Authorization: Bearer YOUR_ADMIN_TOKEN
```

**Response:**
```json
{
  "success": true,
  "message": "Package deleted successfully"
}
```

### 👁️ Get Service Details
```bash
GET /api/v1/services/{service_id}
```

**Example Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "SEMI PERSONAL",
    "slug": "semi-personal",
    "description": "Προσωπική προπόνηση σε μικρότερες ομάδες",
    "trial_price": 1500,
    "is_active": true,
    "display_order": 1,
    "allows_trial": true,
    "max_trial_per_user": 1,
    "created_at": "2024-12-01T10:00:00Z",
    "updated_at": "2024-12-01T10:00:00Z"
  }
}
```

### ✏️ Update Service
```bash
PUT /api/v1/services/{service_id}
Content-Type: application/json
Authorization: Bearer YOUR_ADMIN_TOKEN
```

**Request Body:**
```json
{
  "name": "UPDATED SERVICE NAME",
  "description": "Updated description",
  "trial_price": 3000,
  "is_active": true,
  "display_order": 5,
  "max_trial_per_user": 2
}
```

### 🗑️ Delete Service
```bash
DELETE /api/v1/services/{service_id}
Authorization: Bearer YOUR_ADMIN_TOKEN
```

**Response:**
```json
{
  "success": true,
  "message": "Service deleted successfully"
}
```

### 🔄 Toggle Service Status
```bash
POST /api/v1/services/{service_id}/toggle-active
Authorization: Bearer YOUR_ADMIN_TOKEN
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "SEMI PERSONAL",
    "is_active": false
  },
  "message": "Service deactivated successfully"
}
```

### 🎯 Get Service Trial Info
```bash
GET /api/v1/services/{service_id}/trial-info
```

**Response:**
```json
{
  "success": true,
  "data": {
    "service": {
      "id": 1,
      "name": "SEMI PERSONAL",
      "trial_price": 1500
    },
    "allows_trial": true,
    "trial_price": 1500,
    "max_trial_per_user": 1,
    "user_trial_count": 0,
    "can_book_trial": true,
    "has_active_subscription": false,
    "subscription_reason": "Δεν έχετε ενεργή συνδρομή"
  }
}
```

---

## 📅 Trial Appointments Management

### 📋 List Trial Appointments
```bash
GET /api/v1/trial-appointments?page=1&per_page=15&status=pending&service_id=1
Authorization: Bearer YOUR_ADMIN_TOKEN
```

**Query Parameters:**
- `page`: Page number
- `per_page`: Items per page (max: 100)
- `status`: Filter by status (pending, confirmed, completed, cancelled, no_show)
- `service_id`: Filter by service ID
- `date_from`: Filter from date (YYYY-MM-DD)
- `date_to`: Filter to date (YYYY-MM-DD)

### ➕ Create Trial Appointment
```bash
POST /api/v1/trial-appointments
Content-Type: application/json
Authorization: Bearer YOUR_ADMIN_TOKEN
```

**Request Body:**
```json
{
  "service_id": 1,
  "appointment_date": "2024-12-15",
  "appointment_time": "14:30",
  "notes": "First time client - needs introduction"
}
```

**Required Fields:**
- `service_id` (integer): Service ID
- `appointment_date` (string): Date in YYYY-MM-DD format
- `appointment_time` (string): Time in HH:MM format

### 👁️ Get Trial Appointment Details
```bash
GET /api/v1/trial-appointments/{appointment_id}
Authorization: Bearer YOUR_ADMIN_TOKEN
```

### ✏️ Update Trial Appointment
```bash
PUT /api/v1/trial-appointments/{appointment_id}
Content-Type: application/json
Authorization: Bearer YOUR_ADMIN_TOKEN
```

**Request Body:**
```json
{
  "appointment_date": "2024-12-16",
  "appointment_time": "15:00",
  "notes": "Updated appointment notes"
}
```

### ✅ Confirm Trial Appointment
```bash
POST /api/v1/trial-appointments/{appointment_id}/confirm
Authorization: Bearer YOUR_ADMIN_TOKEN
```

### 🎯 Complete Trial Appointment
```bash
POST /api/v1/trial-appointments/{appointment_id}/complete
Authorization: Bearer YOUR_ADMIN_TOKEN
```

### ❌ Cancel Trial Appointment
```bash
DELETE /api/v1/trial-appointments/{appointment_id}
Authorization: Bearer YOUR_ADMIN_TOKEN
```

### 👤 Get User's Trial Appointments
```bash
GET /api/v1/trial-appointments/user/my-trials?status=confirmed&page=1&per_page=15
Authorization: Bearer YOUR_ADMIN_TOKEN
```

---

## 📝 Appointment Requests Management

### 📋 List Appointment Requests
```bash
GET /api/v1/appointment-requests?page=1&per_page=15&status=pending&service_id=1
Authorization: Bearer YOUR_ADMIN_TOKEN
```

**Query Parameters:**
- `page`: Page number
- `per_page`: Items per page (max: 100)
- `status`: Filter by status (pending, confirmed, cancelled, completed)
- `service_id`: Filter by service ID

### ➕ Create Appointment Request
```bash
POST /api/v1/appointment-requests
Content-Type: application/json
Authorization: Bearer YOUR_ADMIN_TOKEN
```

**Request Body:**
```json
{
  "specialized_service_id": 1,
  "service_id": 1,
  "client_name": "John Doe",
  "client_phone": "+306971234567",
  "client_email": "john@example.com",
  "preferred_time_slots": [
    {
      "date": "2024-12-15",
      "time": "14:30"
    },
    {
      "date": "2024-12-16",
      "time": "10:00"
    }
  ],
  "notes": "First time client - needs introduction"
}
```

**Required Fields:**
- `specialized_service_id` (integer): Specialized service ID
- `client_name` (string): Client full name
- `client_phone` (string): Client phone number
- `preferred_time_slots` (array): Array of preferred date/time slots

### 👁️ Get Appointment Request Details
```bash
GET /api/v1/appointment-requests/{request_id}
Authorization: Bearer YOUR_ADMIN_TOKEN
```

### ✏️ Update Appointment Request
```bash
PUT /api/v1/appointment-requests/{request_id}
Content-Type: application/json
Authorization: Bearer YOUR_ADMIN_TOKEN
```

**Request Body:**
```json
{
  "service_id": 1,
  "instructor_id": 123,
  "status": "confirmed",
  "confirmed_date": "2024-12-15",
  "confirmed_time": "14:30",
  "notes": "Appointment confirmed for 15th December"
}
```

### 🗑️ Delete Appointment Request
```bash
DELETE /api/v1/appointment-requests/{request_id}
Authorization: Bearer YOUR_ADMIN_TOKEN
```

---

## 📊 Business Logic & Rules

### 🎯 Trial Appointment Rules

1. **Active Subscribers**: Unlimited trial appointments for their subscribed services
2. **Non-Subscribers**: Limited to `max_trial_per_user` per service
3. **Validation**: Prevents double bookings at same time/date
4. **Subscription Check**: Automatic verification of active subscriptions

### 📅 Booking Business Rules

1. **Service Package Validation**: Before creating a booking, system checks:
   - User has an active package for the selected service
   - Package has remaining sessions (> 0)
   - Package is not expired

2. **🔍 Smart Service Detection**: If `service_id` is not provided, system automatically detects the service from `class_name`:
   - **EMS Συνεδρία** → EMS TRAINING
   - **Προσωπική Προπόνηση** → PERSONAL TRAINING
   - **Pilates Personal** → PILATES PERSONAL
   - **Pilates Group** → PILATES GROUP
   - **Cardio Personal** → CARDIO PERSONAL
   - **Semi Personal** → SEMI PERSONAL

3. **Error Messages**:
   - `INSUFFICIENT_SESSIONS_FOR_SERVICE`: "Δεν έχετε ενεργό πακέτο με διαθέσιμες συνεδρίες για [Service Name]."
   - `NO_SESSIONS_REMAINING`: "Έχετε εξαντλήσει όλες τις συνεδρίες για [Service Name]."
   - `INSUFFICIENT_SESSIONS`: "Δεν έχετε διαθέσιμες συνεδρίες σε κανένα πακέτο σας."

4. **Fallback Logic**: If no service detected and no `service_id` provided, system checks for any available sessions

### 💎 Custom Package Management

#### **Overview**
The Custom Package Management system allows administrators to assign personalized packages to users with custom pricing, duration, and session counts. This is ideal for VIP customers, special promotions, or negotiated terms.

#### **Key Features**
- **Personalized Pricing**: Set custom prices per user
- **Flexible Duration**: Adjust package validity period
- **Custom Session Count**: Modify number of available sessions
- **VIP Treatment Tracking**: Mark users with special treatment
- **Audit Trail**: Track who assigned custom packages and when

#### **API Endpoints**

**1. Get Eligible Users**
```
GET /api/v1/custom-packages/users
```
Returns users who can receive custom packages with their current package status.

**2. Get Available Packages**
```
GET /api/v1/custom-packages/available-packages
```
Returns all active packages that can be customized.

**3. Get User's Custom Packages**
```
GET /api/v1/custom-packages/user/{userId}
```
Returns all custom packages assigned to a specific user.

> **Note**: The correct endpoint is `/api/v1/custom-packages/user/{userId}` (NOT `/api/v1/packages/custom/user/{userId}`)

> **Important**: Custom packages must have `is_custom_package = true` and appropriate custom values to appear in user profiles. Use the CustomPackageController for proper creation.

## 🔧 **Bulk Package Operations - Fixed Issues**

### **Fixed: "Invalid Date" Error in Extension Operations**

**Problem:** The bulk package extension feature was throwing "invalid date" errors when processing date calculations.

**Root Causes Fixed:**
1. **Carbon::parse() Exception**: The original code used `Carbon::parse()` which throws exceptions for invalid dates
2. **Null Expiry Date Handling**: No fallback when packages had no expiry_date
3. **JSON Encoding Issues**: Carbon objects in history logs caused serialization errors

**Solutions Applied:**
1. **Safe Date Parsing**: Added try-catch blocks and fallback date parsing methods
2. **Null Date Handling**: Default to current date when no expiry_date exists
3. **JSON Serialization**: Convert Carbon objects to ISO strings before JSON encoding

**Code Changes:**
```php
// Before (problematic)
$newExpiry = Carbon::parse($extensionData['set_expiry_date']);

// After (safe)
try {
    $dateString = $extensionData['set_expiry_date'];
    if (is_string($dateString) && !empty($dateString)) {
        $newExpiry = Carbon::createFromFormat('Y-m-d', $dateString);
        if (!$newExpiry) {
            $newExpiry = Carbon::parse($dateString);
        }
    }
} catch (Exception $e) {
    // Return original expiry if calculation fails
    return $currentExpiry;
}
```

## 🧊 **Package Freeze/Pause System**

### **How Freeze Works:**

**Freeze (Παύση) = Extension (Επέκταση)**
- ✅ **ΝΑΙ**, οι ημέρες παύσης προστίθενται στην ημερομηνία λήξης
- ✅ Όταν ένα πακέτο είναι σε παύση, ο χρόνος σταματάει
- ✅ Όταν ξεπαγώνει, οι ημέρες παύσης προστίθενται στην ημερομηνία λήξης

**Example Scenario:**
```
Πακέτο λήγει: 15 Οκτωβρίου 2025
Γίνεται freeze για 10 ημέρες (γυμναστήριο κλειστό)
Ξεπαγώνει: 25 Οκτωβρίου 2025 (+10 ημέρες)
```

**Freeze Process:**
1. **Freeze**: Καταγράφεται `frozen_at` και `freeze_duration_days`
2. **During Freeze**: Ημερομηνία λήξης παραμένει αμετάβλητη
3. **Unfreeze**: Υπολογίζονται οι ημέρες freeze και προστίθενται στην ημερομηνία λήξης

**API Endpoints:**
- `POST /api/user-packages/{userPackage}/freeze` - Freeze με διάρκεια σε ημέρες
- `POST /api/user-packages/{userPackage}/unfreeze` - Unfreeze και αυτόματη επέκταση

### Regular vs Custom Packages

**Regular Packages:**
- Created via PackageController or admin interface
- Use the original package price from the packages table
- `is_custom_package = false`
- Assigned directly to users with standard pricing

**Custom Packages:**
- Created via CustomPackageController (`POST /api/v1/custom-packages`)
- Can have modified pricing, sessions, or duration
- `is_custom_package = true`
- Show savings and special treatment in user profiles
- Can coexist with regular packages of the same base package
- Only prevents true duplicates (same user + same package + custom flag)

### User Profile Response with Package Pricing

When retrieving user data via `GET /api/users/{id}`, each package now includes both the original package price and any custom pricing:

```json
{
  "packages": [
    {
      "id": 20,
      "name": "PERSONAL TRAINING",
      "custom_price": null,
      "is_custom_package": false,
      "package": {
        "id": 9,
        "name": "PERSONAL TRAINING",
        "price": 240.00,
        "sessions": 8,
        "duration": 30
      },
      "original_package_price": 240.00,
      "original_package_sessions": 8,
      "original_package_duration": 30
    },
    {
      "id": 21,
      "name": "PERSONAL TRAINING custom",
      "custom_price": "200.00",
      "is_custom_package": true,
      "package": {
        "id": 10,
        "name": "PERSONAL TRAINING custom",
        "price": 250.00,
        "sessions": 8,
        "duration": 30
      },
      "original_package_price": 250.00,
      "original_package_sessions": 8,
      "original_package_duration": 30
    }
  ]
}
```

**Example showing the difference:**

| Package Type | Package Name | Original Price | Custom Price | Effective Price | Savings | is_custom_package |
|--------------|-------------|---------------|-------------|----------------|---------|------------------|
| Regular | PERSONAL TRAINING | €240.00 | N/A | €240.00 | €0 | false |
| Custom | PERSONAL TRAINING | €240.00 | €200.00 | €200.00 | €40 | true |
| Regular | PERSONAL TRAINING | €20.00 | N/A | €20.00 | €0 | false |
| Custom | PERSONAL TRAINING | €20.00 | €18.00 | €18.00 | €2 | true |

*Note: Users can have multiple packages with the same name - both regular and custom versions coexist.*

**New Fields Added:**
- `original_package_price`: The original price from the packages table
- `original_package_sessions`: The original number of sessions from the packages table
- `original_package_duration`: The original duration in days from the packages table

**4. Create Custom Package**
```
POST /api/v1/custom-packages
```
```json
{
  "user_id": 69,
  "package_id": 1,
  "custom_price": 15000.00,
  "custom_sessions": 25,
  "custom_duration_days": 90,
  "custom_notes": "VIP customer - ειδική τιμή για μακροχρόνια συνεργασία",
  "assigned_by": "Admin Demo"
}
```

**5. Update Custom Package**
```
PUT /api/v1/custom-packages/{userPackageId}
```
Update custom package terms, pricing, or status.

**6. Delete Custom Package**
```
DELETE /api/v1/custom-packages/{userPackageId}
```
Remove custom package assignment.

#### **Business Rules for Custom Packages**

1. **No Duplicate Packages**: Users cannot have multiple active packages of the same type
2. **Automatic Expiry**: Custom packages expire based on custom_duration_days
3. **Session Tracking**: System tracks remaining sessions for custom packages
4. **Audit Logging**: All custom package assignments are logged
5. **VIP Status**: Users with custom packages are marked as having special treatment

#### **Custom Package Fields**

| Field | Type | Description |
|-------|------|-------------|
| `is_custom_package` | boolean | Marks this as a custom package |
| `custom_price` | decimal | Custom price (in cents) |
| `custom_sessions` | integer | Custom number of sessions |
| `custom_duration_days` | integer | Custom validity period |
| `custom_notes` | text | Notes about the custom assignment |
| `assigned_by` | string | Admin who assigned the package |
| `custom_assigned_at` | datetime | When the custom package was assigned |

#### **User Profile Integration**

Custom packages appear in user profiles with:
- ✅ Special VIP indicator
- ✅ Custom pricing information
- ✅ Savings calculation
- ✅ Original vs. custom terms comparison
- ✅ Assignment details and notes

#### **Example Use Cases**

**VIP Customer Discount:**
- Original Package: €200 for 20 sessions, 60 days
- Custom Package: €150 for 25 sessions, 90 days
- Result: Customer gets better value with discount

**Extended Trial Period:**
- Original Package: €50 for 5 sessions, 30 days
- Custom Package: €25 for 5 sessions, 60 days
- Result: Customer gets double time to try the service

**Bulk Purchase Discount:**
- Original Package: €300 for 30 sessions, 90 days
- Custom Package: €250 for 35 sessions, 90 days
- Result: Customer saves €50 and gets 5 extra sessions

---

## 🔍 Special Pricing Check System

### **Overview**
The Special Pricing Check System allows administrators to quickly determine if a customer has custom pricing for specific services. This is essential for accurate pricing calculations and customer service.

### **Key Features**
- ✅ **Service-Specific Checks**: Check if user has custom pricing for any service
- ✅ **Detailed Comparisons**: Compare original vs custom terms
- ✅ **Real-time Validation**: Instant pricing verification
- ✅ **Savings Calculation**: Automatic savings computation
- ✅ **Audit Trail**: Track custom pricing assignments

### **API Endpoints**

#### **1. Quick Special Pricing Check**
```
GET /api/v1/custom-packages/check-special-pricing?user_id={userId}&service_id={serviceId}
```
**Purpose**: Quick boolean check if user has special pricing for a service
**Response**:
```json
{
  "success": true,
  "data": {
    "has_special_pricing": true,
    "service_id": 2,
    "user_id": 69
  }
}
```

#### **2. Detailed Special Pricing Information**
```
GET /api/v1/custom-packages/special-pricing-details?user_id={userId}&service_id={serviceId}
```
**Purpose**: Get complete details of custom pricing for a user and service
**Response**:
```json
{
  "success": true,
  "data": {
    "has_special_pricing": true,
    "custom_package_id": 19,
    "package_name": "VIP Personal Training Package",
    "service_name": "PERSONAL TRAINING",
    "original_price": 200.00,
    "custom_price": 15000.00,
    "price_difference": 50.00,
    "original_sessions": 20,
    "custom_sessions": 25,
    "sessions_difference": 5,
    "original_duration": 60,
    "custom_duration": 90,
    "duration_difference": 30,
    "remaining_sessions": 25,
    "expiry_date": "2025-12-07",
    "assigned_by": "Admin Demo",
    "assigned_at": "2025-09-07T14:43:43Z",
    "custom_notes": "VIP customer - special pricing for long-term commitment"
  }
}
```

#### **3. User's Complete Special Pricing Summary**
```
GET /api/v1/custom-packages/user/{userId}/special-pricing-summary
```
**Purpose**: Get overview of all user's custom packages and total savings
**Example**: `GET /api/v1/custom-packages/user/69/special-pricing-summary`
**Response**:
```json
{
  "success": true,
  "data": {
    "has_special_pricing": true,
    "total_custom_packages": 2,
    "total_savings": 150.00,
    "packages": [
      {
        "package_id": 19,
        "package_name": "VIP Personal Training Package",
        "service_name": "PERSONAL TRAINING",
        "original_price": 200.00,
        "custom_price": 15000.00,
        "savings": 50.00,
        "remaining_sessions": 25,
        "expiry_date": "2025-12-07",
        "status": "active"
      }
    ]
  }
}
```

#### **4. Complete Pricing Comparison**
```
GET /api/v1/custom-packages/user/{userId}/special-pricing-comparison
```
**Purpose**: Compare all user's packages (custom vs standard pricing)
**Example**: `GET /api/v1/custom-packages/user/69/special-pricing-comparison`
**Response**:
```json
{
  "success": true,
  "data": {
    "user_id": 69,
    "total_packages": 2,
    "custom_packages_count": 1,
    "regular_packages_count": 1,
    "packages": [
      {
        "package_id": 19,
        "package_name": "Custom VIP Package για Maria Alexiou",
        "service_name": "PERSONAL TRAINING",
        "is_custom_package": true,
        "status": "active",
        "remaining_sessions": 25,
        "expiry_date": "2025-12-07",
        "pricing": {
          "type": "custom",
          "original_price": 50.00,
          "custom_price": 15000.00,
          "savings": -14950.00,
          "original_sessions": 30,
          "custom_sessions": 25,
          "original_duration": 60,
          "custom_duration": 90
        }
      },
      {
        "package_id": 20,
        "package_name": "membership test",
        "service_name": "PERSONAL TRAINING",
        "is_custom_package": false,
        "status": "active",
        "remaining_sessions": 30,
        "expiry_date": "2025-09-07",
        "pricing": {
          "type": "standard",
          "price": 50.00,
          "sessions": 30,
          "duration": 60
        }
      }
    ]
  }
}
```

### **Helper Methods in UserPackage Model**

```php
// Check if user has special pricing for a service
UserPackage::hasSpecialPricingForService($userId, $serviceId);

// Get detailed special pricing information
UserPackage::getSpecialPricingForService($userId, $serviceId);

// Get complete user special pricing summary
UserPackage::getUserSpecialPricingSummary($userId);
```

### **Frontend Integration Examples**

#### **React Hook for Special Pricing Check**
```typescript
// hooks/useSpecialPricing.ts
export function useSpecialPricing() {
  const checkSpecialPricing = async (userId: number, serviceId: number) => {
    const response = await api.get('/api/v1/custom-packages/check-special-pricing', {
      params: { user_id: userId, service_id: serviceId }
    });
    return response.data.data;
  };

  const getSpecialPricingDetails = async (userId: number, serviceId: number) => {
    const response = await api.get('/api/v1/custom-packages/special-pricing-details', {
      params: { user_id: userId, service_id: serviceId }
    });
    return response.data.data;
  };

  return {
    checkSpecialPricing,
    getSpecialPricingDetails,
  };
}
```

#### **Component for Special Pricing Display**
```typescript
// components/SpecialPricingIndicator.tsx
export function SpecialPricingIndicator({ userId, serviceId }: {
  userId: number;
  serviceId: number;
}) {
  const { data: specialPricing } = useQuery({
    queryKey: ['special-pricing', userId, serviceId],
    queryFn: () => getSpecialPricingDetails(userId, serviceId),
  });

  if (!specialPricing?.has_special_pricing) {
    return null;
  }

  return (
    <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
      <div className="flex items-center gap-2 mb-2">
        <Crown className="h-5 w-5 text-yellow-600" />
        <span className="font-medium text-yellow-800">VIP Special Pricing</span>
      </div>

      <div className="grid grid-cols-2 gap-4 text-sm">
        <div>
          <span className="text-gray-600">Original Price:</span>
          <span className="ml-2 line-through">€{specialPricing.original_price}</span>
        </div>
        <div>
          <span className="text-gray-600">Your Price:</span>
          <span className="ml-2 font-semibold text-green-600">
            €{specialPricing.custom_price}
          </span>
        </div>
        <div>
          <span className="text-gray-600">Savings:</span>
          <span className="ml-2 font-semibold text-green-600">
            €{specialPricing.price_difference}
          </span>
        </div>
        <div>
          <span className="text-gray-600">Extra Sessions:</span>
          <span className="ml-2 font-semibold text-blue-600">
            +{specialPricing.sessions_difference}
          </span>
        </div>
      </div>
    </div>
  );
}
```

### **Business Logic Integration**

#### **Booking System Integration**
```typescript
// Before creating booking, check for special pricing
const validateBooking = async (userId: number, serviceId: number) => {
  // Check if user has special pricing for this service
  const specialPricing = await getSpecialPricingDetails(userId, serviceId);

  if (specialPricing.has_special_pricing) {
    // Use custom package validation rules
    return validateCustomPackageBooking(userId, serviceId, specialPricing);
  } else {
    // Use standard package validation
    return validateStandardPackageBooking(userId, serviceId);
  }
};
```

#### **Pricing Display Integration**
```typescript
// Display correct pricing based on user's special pricing
const getDisplayPrice = (userId: number, serviceId: number, originalPrice: number) => {
  const specialPricing = getSpecialPricingDetails(userId, serviceId);

  if (specialPricing.has_special_pricing) {
    return {
      originalPrice: specialPricing.original_price,
      displayPrice: specialPricing.custom_price,
      savings: specialPricing.price_difference,
      isSpecialPricing: true
    };
  }

  return {
    originalPrice,
    displayPrice: originalPrice,
    savings: 0,
    isSpecialPricing: false
  };
};
```

### **Testing the Special Pricing System**

#### **Test Case 1: User with Special Pricing**
```bash
# Check if Maria Alexiou has special pricing for Personal Training
curl -X GET "http://localhost:8000/api/v1/custom-packages/check-special-pricing?user_id=69&service_id=2" \
  -H "Authorization: Bearer {token}"

# Expected Response:
{
  "success": true,
  "data": {
    "has_special_pricing": true,
    "service_id": 2,
    "user_id": 69
  }
}
```

#### **Test Case 2: User without Special Pricing**
```bash
# Check if a regular user has special pricing for EMS
curl -X GET "http://localhost:8000/api/v1/custom-packages/check-special-pricing?user_id=70&service_id=5" \
  -H "Authorization: Bearer {token}"

# Expected Response:
{
  "success": true,
  "data": {
    "has_special_pricing": false,
    "service_id": 5,
    "user_id": 70
  }
}
```

#### **Test Case 3: Complete Pricing Comparison**
```bash
# Get all pricing information for Maria Alexiou
curl -X GET "http://localhost:8000/api/v1/custom-packages/user/69/special-pricing-comparison" \
  -H "Authorization: Bearer {token}"

# Expected Response: Complete comparison of all packages with pricing details
```

### **Performance Considerations**
- ✅ **Database Indexing**: Index on `user_id`, `service_id`, `is_custom_package`
- ✅ **Caching**: Cache frequently accessed special pricing data
- ✅ **Batch Queries**: Use eager loading to minimize database queries
- ✅ **Response Optimization**: Return only necessary data for performance

### **Security Considerations**
- ✅ **Admin Only Access**: All special pricing endpoints require admin role
- ✅ **User Data Protection**: Only admins can view user's special pricing details
- ✅ **Audit Logging**: All special pricing checks are logged
- ✅ **Data Validation**: Strict validation on all input parameters

---

**🎯 This Special Pricing Check System provides administrators with powerful tools to quickly identify and manage customer special pricing, ensuring accurate billing and excellent customer service!** ✨💎🔍

#### 📋 Booking Validation Flow

```bash
1. User attempts to create booking with service_id
2. System checks: Does user have active package for this service?
   ├── YES → Check remaining sessions > 0
   │    ├── YES → ✅ Booking ALLOWED
   │    └── NO → ❌ "Έχετε εξαντλήσει όλες τις συνεδρίες για [Service]"
   └── NO → ❌ "Δεν έχετε ενεργό πακέτο για [Service]"

3. If no service_id provided → Check any available sessions
   ├── YES → ✅ Booking ALLOWED (legacy behavior)
   └── NO → ❌ "Δεν έχετε διαθέσιμες συνεδρίες"
```

#### 🧪 Test Scenarios

**✅ Success Scenario:**
- User has active package for selected service
- Package has remaining sessions > 0
- Result: Booking created successfully

**❌ Failure Scenarios:**
1. **No Package for Service**: User has packages but not for the selected service
2. **No Sessions Remaining**: Package exists but all sessions used
3. **No Active Packages**: User has no active packages at all

### 🔒 Permission Levels

- **Admin**: Full CRUD access to all entities
- **Trainer**: Can manage appointments and classes
- **Member**: Can view/book their own appointments

### 💰 Pricing

- Trial prices are stored in cents (e.g., 2500 = 25€)
- All prices include VAT/taxes
- Prices can be updated per service

---

## 🛠️ Practical Examples

### 📝 Complete Service Management Workflow

```bash
# 1. Create a new service
curl -X POST http://your-domain.com/api/v1/services \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "BOXING TRAINING",
    "slug": "boxing-training",
    "description": "Professional boxing training sessions",
    "trial_price": 3000,
    "is_active": true,
    "display_order": 7,
    "allows_trial": true,
    "max_trial_per_user": 1
  }'

# 2. Get service details
curl -X GET http://your-domain.com/api/v1/services/7 \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN"

# 3. Update service
curl -X PUT http://your-domain.com/api/v1/services/7 \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "trial_price": 3500,
    "max_trial_per_user": 2
  }'

# 4. Toggle service status
curl -X POST http://your-domain.com/api/v1/services/7/toggle-active \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN"
```

### 📅 Complete Trial Appointment Workflow

```bash
# 1. List pending trial appointments
curl -X GET "http://your-domain.com/api/v1/trial-appointments?status=pending&page=1&per_page=10" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN"

# 2. Confirm an appointment
curl -X POST http://your-domain.com/api/v1/trial-appointments/123/confirm \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN"

# 3. Complete an appointment
curl -X POST http://your-domain.com/api/v1/trial-appointments/123/complete \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN"

# 4. Cancel an appointment
curl -X DELETE http://your-domain.com/api/v1/trial-appointments/123 \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN"
```

### 📝 Complete Appointment Request Workflow

```bash
# 1. List pending requests
curl -X GET "http://your-domain.com/api/v1/appointment-requests?status=pending&page=1&per_page=10" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN"

# 2. Update request status
curl -X PUT http://your-domain.com/api/v1/appointment-requests/456 \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "status": "confirmed",
    "confirmed_date": "2024-12-15",
    "confirmed_time": "14:30",
    "instructor_id": 123
  }'

# 3. Delete request if needed
curl -X DELETE http://your-domain.com/api/v1/appointment-requests/456 \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN"
```

---

## 🔍 Advanced Filtering & Search

### 📊 Service Filtering
```bash
# Get only active services
GET /api/v1/services?active_only=true

# Sort by display order
GET /api/v1/services?sort_by=display_order&sort_direction=asc

# Get services with trial allowed
GET /api/v1/services?allows_trial=true
```

### 📅 Appointment Filtering
```bash
# Get appointments by date range
GET /api/v1/trial-appointments?date_from=2024-12-01&date_to=2024-12-31

# Get appointments by service
GET /api/v1/trial-appointments?service_id=1

# Get appointments by status
GET /api/v1/trial-appointments?status=confirmed
```

### 📝 Request Filtering
```bash
# Get requests by service
GET /api/v1/appointment-requests?service_id=1

# Get requests by status
GET /api/v1/appointment-requests?status=pending

# Combine filters
GET /api/v1/appointment-requests?service_id=1&status=confirmed&page=1&per_page=20
```

---

## 🚨 Error Handling

### Common Error Responses

**Validation Error:**
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "service_id": ["The selected service is invalid."],
    "appointment_date": ["The appointment date must be today or later."]
  }
}
```

**Unauthorized:**
```json
{
  "success": false,
  "message": "Unauthenticated."
}
```

**Forbidden:**
```json
{
  "success": false,
  "message": "This action is unauthorized."
}
```

**Not Found:**
```json
{
  "success": false,
  "message": "Service not found"
}
```

---

## 📞 Support & Resources

### 📚 Documentation
- **Swagger UI**: `https://your-domain.com/api-docs`
- **Postman Collection**: Available in repository
- **README**: Comprehensive API documentation

### 🆘 Troubleshooting
1. **401 Unauthorized**: Check your Bearer token
2. **403 Forbidden**: Ensure you have admin privileges
3. **404 Not Found**: Verify the resource ID exists
4. **422 Validation Error**: Check required fields and formats

### 📞 Contact
For API support or questions:
- **Email**: support@sweat93.gr
- **Interactive Documentation**: `https://your-domain.com/api-docs`

---

## 🎯 Quick Reference

### Available Services (IDs)
1. **SEMI PERSONAL** (ID: 1) - 15€ trial
2. **PERSONAL TRAINING** (ID: 2) - 25€ trial
3. **PILATES PERSONAL** (ID: 3) - 20€ trial
4. **PILATES GROUP** (ID: 4) - 12€ trial
5. **EMS TRAINING** (ID: 5) - 30€ trial
6. **CARDIO PERSONAL** (ID: 6) - 18€ trial

### Key Endpoints
- `GET /api/v1/services` - List services
- `POST /api/v1/services` - Create service
- `GET /api/v1/trial-appointments` - List trial appointments
- `POST /api/v1/trial-appointments` - Create trial appointment
- `GET /api/v1/appointment-requests` - List appointment requests
- `POST /api/v1/appointment-requests` - Create appointment request

---

**🎉 Happy Admin-ing!** Use this guide to efficiently manage your Sweat93 Gym system.
