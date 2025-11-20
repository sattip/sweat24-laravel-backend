# Sweat93 Multi-Store Gym API Documentation

## 📋 Overview

This documentation provides comprehensive API documentation for the Sweat93 Gym Management System featuring multi-store cash register functionality, package management, appointment booking, services & trial management, and complete admin capabilities.

## 📚 Documentation Files

### 1. Swagger/OpenAPI Specification
- **File**: `api-docs.yml`
- **Format**: OpenAPI 3.0.3 YAML specification
- **Usage**: Import into Swagger UI, Postman, or any OpenAPI-compatible tool

### 2. Postman Collection
- **File**: `postman-collection.json`
- **Format**: Postman Collection v2.1
- **Usage**: Import directly into Postman

---

## 🚀 Quick Start

### Using Swagger UI (Recommended)

**Option A: Built-in Swagger UI**
1. **Access the live documentation**:
   ```
   https://api.sweat93.gr/api-docs
   ```

2. **Features included**:
   - ✅ Interactive API testing
   - ✅ Authentication handling
   - ✅ Request/response examples
   - ✅ Schema validation
   - ✅ Beautiful, responsive UI

**Option B: External Swagger UI**
1. **Install Swagger UI** (if not already available):
   ```bash
   npm install -g swagger-ui
   ```

2. **Serve the documentation**:
   ```bash
   swagger-ui api-docs.yml
   ```

3. **Access the interactive documentation** at `http://localhost:8080`

### Using Postman

1. **Import the collection**:
   - Open Postman
   - Click "Import" button
   - Select "File"
   - Choose `postman-collection.json`

2. **Configure environment variables**:
   - Create a new environment named "Sweat93 API"
   - Set variables:
     ```
     base_url: https://api.sweat93.gr
     bearer_token: (will be set automatically after login)
     ```

3. **Start testing**:
   - Begin with the "Login" request in Authentication folder
   - Token will be automatically saved for subsequent requests

---

## 🔐 Authentication

All protected endpoints require Bearer token authentication:

```http
Authorization: Bearer {your_token_here}
```

### Getting Started with Authentication

1. **Login** using `/api/v1/auth/login`
2. **Copy the token** from the response
3. **Use the token** in all subsequent requests

**Example Login Request:**
```json
{
  "email": "admin@sweat93.gr",
  "password": "password123"
}
```

---

## 🏪 Multi-Store System

The API supports multiple stores ("Βάρη", "Λαγονήσι") with:

- **Store-specific cash registers**
- **Automated income posting** per completed training
- **Per-store financial reports**
- **Package usage tracking**
- **Custom store colors** for UI differentiation (#10B981 for Βάρη, #F59E0B for Λαγονήσι)

### Key Multi-Store Endpoints

#### Complete Booking & Auto-Income
```http
POST /api/v1/bookings/{id}/complete
```

**Business Logic:**
- Validates store assignment
- Decrements package sessions
- Calculates per-training cost: `package_price / total_sessions`
- Handles rounding for last session
- Creates cash register entry automatically

#### Store Cash Register
```http
GET /api/v1/stores/{storeId}/cash-entries
GET /api/v1/stores/{storeId}/report
POST /api/v1/expenses
```

#### Package Management
```http
GET /api/v1/customers/{userId}/packages
GET /api/v1/packages/{packageId}/report
```

---

## 📊 API Endpoints Overview

### Authentication & Registration
- `POST /api/v1/auth/login` - User login
- `POST /api/v1/auth/logout` - User logout
- `GET /api/v1/auth/me` - Get current user
- `POST /api/v1/registration/initial` - Start registration
- `POST /api/v1/registration/accept-terms` - Accept terms
- `POST /api/v1/registration/complete` - Complete registration

### Store Management (Admin Only)
- `GET /api/v1/admin/stores` - List all stores
- `POST /api/v1/admin/stores` - Create new store
- `GET /api/v1/admin/stores/{id}` - Get store details
- `PUT /api/v1/admin/stores/{id}` - Update store
- `DELETE /api/v1/admin/stores/{id}` - Delete store
- `POST /api/v1/admin/stores/{id}/toggle-status` - Toggle store active/inactive
- `GET /api/v1/admin/stores/{id}/statistics` - Get store statistics

### Cash Register Management (Total & Store-Specific)
- `GET /api/v1/cash-register` - List all cash register entries (across all stores)
- `GET /api/v1/cash-register?store_id={id}` - List cash register entries by store
- `POST /api/v1/cash-register` - Create cash register entry (requires store_id)
- `GET /api/v1/cash-register/limited` - Limited access for trainers (all stores)
- `GET /api/v1/cash-register/limited?store_id={id}` - Limited access for trainers by store
- `GET /api/v1/cash-register/{id}` - Get cash register entry
- `PUT /api/v1/cash-register/{id}` - Update cash register entry
- `DELETE /api/v1/cash-register/{id}` - Delete cash register entry
- `GET /api/v1/cash-register/summary` - Get cash register summary by store

### Expense Categories Management
- `GET /api/v1/expense-categories` - List expense categories
- `POST /api/v1/expense-categories` - Create expense category
- `GET /api/v1/expense-categories/tree` - Get categories tree structure
- `GET /api/v1/expense-categories/options` - Get categories for dropdown
- `GET /api/v1/expense-categories/{id}` - Get expense category
- `PUT /api/v1/expense-categories/{id}` - Update expense category
- `DELETE /api/v1/expense-categories/{id}` - Delete expense category
- `POST /api/v1/expense-categories/{id}/toggle-status` - Toggle category status

### Business Expenses Management (Total & Store-Specific)
- `GET /api/v1/business-expenses` - List all business expenses (across all stores)
- `GET /api/v1/business-expenses?store_id={id}` - List business expenses by store
- `POST /api/v1/business-expenses` - Create business expense (requires store_id)
- `GET /api/v1/business-expenses/{id}` - Get business expense
- `PUT /api/v1/business-expenses/{id}` - Update business expense
- `DELETE /api/v1/business-expenses/{id}` - Delete business expense

### Multi-Store Cash Register
- `POST /api/v1/bookings/{id}/complete` - Complete booking with auto-income
- `GET /api/v1/stores/{id}/cash-entries` - Get store cash entries
- `GET /api/v1/stores/{id}/report` - Get store financial report
- `POST /api/v1/expenses` - Record expense
- `GET /api/v1/customers/{id}/packages` - Get user packages
- `GET /api/v1/packages/{id}/report` - Get package consumption report

### Bookings Management (Total & Store-Specific)
- `GET /api/v1/bookings` - List all bookings (across all stores)
- `GET /api/v1/bookings?store_id={id}` - List bookings by store
- `POST /api/v1/bookings` - Create booking (requires store_id)
- `GET /api/v1/bookings/{id}` - Get booking details
- `PUT /api/v1/bookings/{id}` - Update booking
- `DELETE /api/v1/bookings/{id}` - Cancel booking

### Packages Management
- `GET /api/v1/packages` - List packages
- `POST /api/v1/packages` - Create package
- `GET /api/v1/packages/{id}` - Get package details
- `PUT /api/v1/packages/{id}` - Update package
- `DELETE /api/v1/packages/{id}` - Delete package

### Users Management
- `GET /api/v1/users` - List users
- `POST /api/v1/users` - Create user
- `GET /api/v1/users/{id}` - Get user details
- `PUT /api/v1/users/{id}` - Update user
- `DELETE /api/v1/users/{id}` - Delete user

### Classes Management
- `GET /api/v1/classes` - List classes
- `POST /api/v1/classes` - Create class
- `GET /api/v1/classes/{id}` - Get class details
- `PUT /api/v1/classes/{id}` - Update class
- `DELETE /api/v1/classes/{id}` - Delete class

### Dashboard & Analytics
- `GET /api/v1/dashboard/stats` - Get dashboard statistics
- `GET /api/v1/dashboard/activities` - Get recent activities

---

## 🎯 Key Features & Business Logic

### 1. Automated Income Posting
When a booking is completed, the system automatically:
- Validates package exists and has remaining sessions
- Calculates per-training cost (`package_price / total_sessions`)
- Handles rounding for last session to match package total
- Creates cash register entry linked to the booking's store

**Example:** €100 package ÷ 3 sessions = €33.33, €33.33, €33.34

### 2. Store-Based Financial Tracking
- Each store maintains its own cash register
- Income automatically posted to booking's store
- Expenses can be recorded per store
- Financial reports generated per store

### 3. Package Lifecycle Management
- Track remaining sessions per user package
- Automatic session deduction on booking completion
- Package expiry and renewal management
- Usage reports and statistics

### 4. Multi-Service Package Support
The system now supports packages that can belong to multiple services simultaneously, providing greater flexibility:

**Key Benefits:**
- ✅ **Cross-Service Packages**: Create packages that work across multiple service types
- ✅ **Flexible Service Combinations**: Mix and match services (e.g., Personal Training + Pilates)
- ✅ **Enhanced User Experience**: Users can purchase comprehensive packages covering multiple areas
- ✅ **Backward Compatibility**: Existing single-service packages continue to work seamlessly

**Example Use Cases:**
- **Wellness Package**: Personal Training + Pilates + Cardio (3 services in 1 package)
- **Family Package**: Parent-Child sessions across different service types
- **Comprehensive Training**: Multiple training modalities in one convenient package

**API Changes:**
```javascript
// Old format (single service)
{
  "service_id": 1
}

// New format (multiple services)
{
  "service_ids": [1, 2, 3]
}
```

**Database Structure:**
- **Before**: `packages.service_id` (single foreign key)
- **After**: `package_service` pivot table (many-to-many relationship)
- **Migration**: Automatic migration of existing data preserved

### 5. Role-Based Access Control
- **Admin**: Full system access
- **Trainer**: Can complete bookings and view reports
- **Member**: Limited access to own data

---

## 🔧 Environment Setup

### Postman Environment Variables
Create these variables in your Postman environment:

```json
{
  "base_url": "https://api.sweat93.gr",
  "bearer_token": "",
  "store_id": "1",
  "user_id": "",
  "booking_id": "",
  "package_id": ""
}
```

### Testing Workflow

1. **Login** → Get authentication token
2. **Create/List** entities (users, packages, classes)
3. **Create booking** → Store booking ID
4. **Complete booking** → Auto-income posting
5. **Check cash register** → Verify income recorded
6. **View reports** → Analyze financial data

---

## 📝 Request/Response Examples

### Complete Booking Request
```http
POST /api/v1/bookings/123/complete
Authorization: Bearer {token}
Content-Type: application/json

{
  "completed_by": 1
}
```

### Complete Booking Response (Current Format)
```json
{
  "success": true,
  "message": "Booking completed successfully",
  "data": {
    "booking": {
      "id": 123,
      "status": "completed",
      "store_id": 1
    },
    "user_package": {
      "id": 456,
      "remaining_sessions": 7,
      "used_sessions": 3
    },
    "cash_entry": {
      "id": 789,
      "type": "income",
      "amount": 33.33,
      "store_id": 1,
      "description": "Package usage - Premium Package"
    }
  }
}
```

### Proposed Enhanced Success Response Format
```json
{
  "success": true,
  "message": "Το ραντεβού ολοκληρώθηκε επιτυχώς.",
  "data": {
    "appointment_id": 123,
    "booking_details": {
      "status": "completed",
      "store_id": 1
    },
    "user_package": {
      "remaining_sessions": 7,
      "used_sessions": 3
    }
  },
  "timestamp": "2025-09-07T08:20:41.465943Z"
}
```

### Store Financial Report
```http
GET /api/v1/stores/1/report?start_date=2024-01-01&end_date=2024-12-31
```

```json
{
  "success": true,
  "data": {
    "store_id": 1,
    "income": 2500.50,
    "expenses": 380.25,
    "net": 2120.25,
    "entries_count": 45
  },
  "store": {
    "id": 1,
    "name": "Βάρη"
  }
}
```

---

## 🐛 Error Handling

### Common Error Responses

#### Validation Error
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "email": ["The email field is required."],
    "password": ["The password must be at least 8 characters."]
  }
}
```

#### Authentication Error
```json
{
  "success": false,
  "message": "Unauthenticated."
}
```

#### Business Logic Error (Current Format)
```json
{
  "success": false,
  "message": "Package has no remaining sessions"
}
```

#### Proposed Enhanced Business Logic Error Format
```json
{
  "success": false,
  "message": "Δεν έχετε διαθέσιμες συνεδρίες στο πακέτο σας.",
  "error_code": "INSUFFICIENT_SESSIONS",
  "error_type": "business_validation",
  "timestamp": "2025-09-07T08:20:41.465943Z"
}
```

---

## 🔄 Rate Limiting

- **Authenticated requests**: 60 per minute
- **Public endpoints**: 30 per minute
- **Login attempts**: 5 per minute

---

## 📞 Support

For API support or questions:
- **Email**: support@sweat93.gr
- **Interactive Swagger UI**: `https://your-domain.com/api-docs`
- **OpenAPI YAML**: `https://your-domain.com/api-docs/spec`
- **Postman Collection**: `https://your-domain.com/api-docs/postman`
- **Documentation**: This README file

---

## 🎯 Services & Trial Appointments Management

### Services Management

The system now includes comprehensive service management for gym offerings with trial booking capabilities. Services are connected to all major entities in the system.

#### Key Features:
- ✅ **Complete CRUD operations** for gym services
- ✅ **Trial booking system** with smart restrictions
- ✅ **Subscription-aware trial logic**:
  - **Active subscribers**: Unlimited trial bookings for their services
  - **Non-subscribers**: Limited to `max_trial_per_user` per service
- ✅ **Service status management** (active/inactive)
- ✅ **Flexible pricing** and configuration

#### Service Trial Logic:
```javascript
// If user has ACTIVE subscription for service → UNLIMITED trials
if (user.hasActiveSubscription(service)) {
    allowUnlimitedTrials = true;
}

// If user has NO active subscription → LIMITED trials
else {
    allowTrials = user.trialCount < service.max_trial_per_user;
}
```

#### Available Services:
1. **SEMI PERSONAL** (15€ trial) - Small group personal training
2. **PERSONAL TRAINING** (25€ trial) - One-on-one personal training
3. **PILATES PERSONAL** (20€ trial) - Private Pilates sessions
4. **PILATES GROUP** (12€ trial) - Group Pilates classes
5. **EMS TRAINING** (30€ trial) - Electrical muscle stimulation
6. **CARDIO PERSONAL** (18€ trial) - Personal cardio training

#### Service Relationships

Services are now integrated throughout the entire system:

**📦 Packages → Services**
- Each package belongs to a specific service
- Packages inherit service properties and trial logic
- Service-based pricing and categorization

**🏋️ Gym Classes → Services**
- Classes are associated with specific services
- Enables service-based filtering and organization
- Supports service-specific booking logic

**📅 Bookings → Services**
- Bookings track which service they belong to
- Enables service-based reporting and analytics
- Supports service-specific business rules

**🔗 Data Flow:**
```
Service → Packages (1:N)
Service → Gym Classes (1:N)
Service → Bookings (1:N)
Service → Trial Appointments (1:N)
Service → Appointment Requests (1:N)
```

### Trial Appointments

Complete trial appointment management system with workflow support.

#### Features:
- ✅ **Full lifecycle management** (pending → confirmed → completed)
- ✅ **Role-based access control**:
  - **Members**: Can book, view, update, cancel their own appointments
  - **Trainers/Admins**: Can manage all appointments
- ✅ **Smart validation** preventing double bookings and conflicts
- ✅ **Status tracking** with timestamps
- ✅ **Comprehensive filtering** and search capabilities

#### Appointment Workflow:
```
PENDING → CONFIRMED → COMPLETED
   ↓         ↓         ↓
CANCELLED CANCELLED   -
```

#### API Endpoints:

**Services:**
- `GET /api/v1/services` - List services
- `POST /api/v1/services` - Create service (Admin)
- `GET /api/v1/services/{id}` - Get service details
- `PUT /api/v1/services/{id}` - Update service (Admin)
- `DELETE /api/v1/services/{id}` - Delete service (Admin)
- `GET /api/v1/services/{id}/trial-info` - Get trial eligibility info

**Trial Appointments:**
- `GET /api/v1/trial-appointments` - List appointments
- `POST /api/v1/trial-appointments` - Create appointment
- `GET /api/v1/trial-appointments/{id}` - Get appointment details
- `PUT /api/v1/trial-appointments/{id}` - Update appointment
- `DELETE /api/v1/trial-appointments/{id}` - Cancel appointment
- `POST /api/v1/trial-appointments/{id}/confirm` - Confirm appointment (Admin/Trainer)
- `POST /api/v1/trial-appointments/{id}/complete` - Complete appointment (Admin/Trainer)
- `GET /api/v1/trial-appointments/user/my-trials` - Get user's appointments

**Appointment Requests:**
- `GET /api/v1/appointment-requests` - List appointment requests (Admin)
- `POST /api/v1/appointment-requests` - Create appointment request
- `GET /api/v1/appointment-requests/{id}` - Get appointment request details
- `PUT /api/v1/appointment-requests/{id}` - Update appointment request (Admin)
- `DELETE /api/v1/appointment-requests/{id}` - Delete appointment request (Admin)

---

## 📋 Change Log

### Version 1.2.0 (Latest)
- ✅ **Multi-Service Package Support** - Packages can now belong to multiple services instead of just one
- ✅ **Enhanced Package Management** - Updated API endpoints to support `service_ids` array
- ✅ **Improved Package Flexibility** - Users can create packages that work across multiple service types
- ✅ **Backward Compatible Migration** - Existing packages automatically migrated to new structure
- ✅ **Updated Documentation** - All docs updated to reflect multi-service package capabilities

### Version 1.1.0
- ✅ **Services Management System**
- ✅ **Trial Appointments Management**
- ✅ **Appointment Requests Management**
- ✅ **Subscription-aware trial logic**
- ✅ **Complete service lifecycle management**
- ✅ **Role-based trial appointment access**
- ✅ **Smart validation and conflict prevention**
- ✅ **Service-entity relationships** (classes, packages, bookings, appointment requests)
- ✅ **Enhanced booking validation** (service-specific package checks)
- ✅ **🔍 Smart service detection** (automatic service identification from class names)
- ✅ **💎 Custom Package Management** (VIP pricing, custom terms, special treatment)
- ✅ **Enhanced API documentation for new features**

### Version 1.0.0
- ✅ Multi-store cash register system
- ✅ Automated income posting
- ✅ Package lifecycle management
- ✅ Complete booking management
- ✅ User authentication & registration
- ✅ Role-based access control
- ✅ Comprehensive API documentation

---

**🎉 Happy API Testing!**

This documentation provides everything you need to effectively use and test the Sweat93 Gym Management API. The multi-store cash register system with automated income posting is a key feature that streamlines financial management across multiple locations.
