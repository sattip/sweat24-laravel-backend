# 🌱 Comprehensive Mock Data Seeding Guide

This guide explains how to use the comprehensive mock data seeders created for the SWEAT24 gym management system. These seeders provide realistic Greek data for thorough testing of all features.

## 📋 Table of Contents

1. [Quick Start](#quick-start)
2. [Seeders Overview](#seeders-overview)
3. [Testing Scenarios](#testing-scenarios)
4. [API Mock Responses](#api-mock-responses)
5. [Database Reset](#database-reset)
6. [Troubleshooting](#troubleshooting)

## 🚀 Quick Start

### Run All Seeders
```bash
cd /path/to/sweat24-laravel-backend
php artisan db:seed
```

### Run Specific Seeder
```bash
php artisan db:seed --class=ComprehensiveUsersSeeder
```

### Fresh Database with All Data
```bash
php artisan migrate:fresh --seed
```

## 📊 Seeders Overview

### 1. **ComprehensiveUsersSeeder**
- **Records**: 20 users total
- **Breakdown**: 
  - 2 Admin users
  - 4 Trainer users  
  - 14 Member users
- **Features**:
  - Realistic Greek names
  - Various membership types
  - Different join dates and activity levels
  - Medical history information
  - Phone numbers and emails

### 2. **EnhancedPackagesSeeder**
- **Records**: 11 packages
- **Types**: 
  - Membership packages (Basic, Premium, Student)
  - Personal training packages (4, 8, 12 sessions)
  - Specialized packages (Yoga/Pilates, EMS)
  - Trial packages
- **Features**:
  - Detailed features and terms
  - Realistic pricing
  - Different durations and session counts

### 3. **EnhancedGymClassesSeeder**
- **Records**: 200+ classes (14 days coverage)
- **Types**:
  - Group fitness (HIIT, Yoga, Pilates, Spinning, Zumba)
  - Personal training slots
  - Specialized classes (EMS, Functional, CrossFit)
- **Features**:
  - Various instructors and locations
  - Different participation levels
  - Realistic scheduling patterns

### 4. **ComprehensiveBookingsSeeder**
- **Records**: 40+ bookings
- **Statuses**: confirmed, pending, cancelled, completed, no_show
- **Features**:
  - Past and future bookings
  - Various cancellation scenarios
  - Attendance tracking
  - Realistic booking times

### 5. **ComprehensiveUserPackagesSeeder**
- **Records**: 50+ user packages
- **Scenarios**:
  - Active packages with long-term validity
  - Packages expiring soon (1-7 days)
  - Recently expired packages
  - Suspended packages
  - Fully used packages
- **Features**:
  - Extension history
  - Payment status tracking
  - Detailed notes and reasons

### 6. **CancellationPoliciesSeeder**
- **Records**: 6 policies
- **Types**:
  - Basic policy for group classes
  - Strict policy for personal training
  - Premium member benefits
  - EMS training specific rules
  - Trial package policies
- **Features**:
  - Time-based charge percentages
  - Instructor and admin overrides
  - Grace periods and free cancellations

### 7. **ComprehensiveNotificationsSeeder**
- **Records**: 13 notifications
- **Types**: welcome, package_expiry, promotion, reminders, system
- **Statuses**: sent, scheduled, draft
- **Features**:
  - Target audience filtering
  - Multiple delivery methods
  - Tracking and analytics
  - Realistic content in Greek

### 8. **EvaluationDataSeeder**
- **Records**: 35+ evaluations
- **Features**:
  - Rating questions (1-5 scale)
  - Text feedback
  - Various class types
  - Realistic user responses
  - Overall satisfaction tracking

### 9. **ComprehensiveActivityLogsSeeder**
- **Records**: 1000+ activity logs (30 days)
- **Types**:
  - User registrations
  - Package purchases
  - Class attendance
  - Payment processing
  - Admin actions
- **Features**:
  - Realistic daily patterns
  - Metadata tracking
  - IP addresses and user agents

### 10. **EnhancedCashRegisterSeeder**
- **Records**: 500+ entries (60 days)
- **Categories**:
  - Package payments
  - Personal training fees
  - Retail sales
  - Owner withdrawals
  - Refunds and adjustments
- **Features**:
  - Daily transaction patterns
  - Multiple payment methods
  - Realistic amounts and descriptions

### 11. **EnhancedBusinessExpensesSeeder**
- **Records**: 200+ expenses (90 days)
- **Categories**:
  - Monthly recurring (rent, utilities, salaries)
  - Weekly recurring (cleaning, supplies)
  - Daily random (equipment, marketing, food)
  - Seasonal (heating, cooling, events)
- **Features**:
  - Approval workflows
  - Vendor tracking
  - Payment method variety

## 🎯 Testing Scenarios

### User Management
- **New users**: Test registration flow
- **Active users**: Various activity levels
- **Expired memberships**: Test renewal processes
- **Different roles**: Admin, trainer, member permissions

### Package Management
- **Expiring packages**: Test renewal reminders
- **Package extensions**: Test goodwill extensions
- **Different types**: Test various pricing models
- **Usage tracking**: Test session consumption

### Booking System
- **Full classes**: Test waitlist functionality
- **Cancellations**: Test various cancellation policies
- **Rescheduling**: Test rescheduling workflows
- **Attendance**: Test check-in/check-out processes

### Financial Management
- **Daily transactions**: Test cash register operations
- **Monthly expenses**: Test expense approval workflows
- **Revenue tracking**: Test financial reporting
- **Payment processing**: Test various payment methods

### Communication System
- **Automated notifications**: Test package expiry alerts
- **Manual notifications**: Test promotional campaigns
- **Targeting**: Test audience segmentation
- **Delivery tracking**: Test open/click rates

### Analytics & Reporting
- **Activity monitoring**: Test dashboard analytics
- **User behavior**: Test engagement tracking
- **Financial reports**: Test revenue analysis
- **Class performance**: Test class popularity metrics

## 🔧 API Mock Responses

For React frontend development, comprehensive mock API responses are available:

### Location
```
/sweat24-admin-panel/src/data/apiMockResponses.ts
```

### Features
- **Dashboard statistics**: Revenue, membership, activity data
- **User management**: CRUD operations with realistic data
- **Booking system**: Create, cancel, reschedule bookings
- **Package management**: Package details and pricing
- **Notifications**: Send, schedule, and track notifications
- **Financial data**: Cash register and expense tracking
- **Reports**: Revenue and membership analytics

### Usage Example
```typescript
import { mockApiResponses, simulateApiDelay } from '@/data/apiMockResponses';

// Simulate API call with delay
async function getUsers() {
  await simulateApiDelay(500);
  return mockApiResponses.users.getUsers();
}
```

## 🔄 Database Reset

### Complete Reset
```bash
php artisan migrate:fresh --seed
```

### Reset Specific Tables
```bash
php artisan migrate:refresh --path=database/migrations/2025_07_08_create_bookings_table.php
php artisan db:seed --class=ComprehensiveBookingsSeeder
```

### Add More Test Data
```bash
php artisan db:seed --class=ComprehensiveUsersSeeder
php artisan db:seed --class=EnhancedCashRegisterSeeder
```

## 🔍 Troubleshooting

### Common Issues

1. **Foreign Key Constraints**
   ```bash
   # Run seeders in correct order
   php artisan db:seed --class=ComprehensiveUsersSeeder
   php artisan db:seed --class=EnhancedPackagesSeeder
   php artisan db:seed --class=ComprehensiveUserPackagesSeeder
   ```

2. **Memory Issues**
   ```bash
   # Increase memory limit
   php -d memory_limit=512M artisan db:seed
   ```

3. **Timeout Issues**
   ```bash
   # Run individual seeders
   php artisan db:seed --class=ComprehensiveActivityLogsSeeder
   ```

### Performance Tips

1. **Disable Query Logging**
   ```php
   // In seeder
   DB::connection()->disableQueryLog();
   ```

2. **Use Batch Inserts**
   ```php
   // Already implemented in all seeders
   Model::insert($batchData);
   ```

3. **Run in Production Mode**
   ```bash
   php artisan optimize
   php artisan config:cache
   php artisan db:seed
   ```

## 📈 Expected Results

After running all seeders, you should have:

- **Total Users**: ~20 (2 admins, 4 trainers, 14 members)
- **Total Classes**: ~200 (14 days of realistic scheduling)
- **Total Bookings**: ~40 (various statuses and scenarios)
- **Total Packages**: 11 (comprehensive pricing options)
- **Total User Packages**: ~50 (various expiry scenarios)
- **Total Notifications**: 13 (sent, scheduled, draft)
- **Total Activity Logs**: ~1000 (30 days of activity)
- **Total Cash Entries**: ~500 (60 days of transactions)
- **Total Expenses**: ~200 (90 days of business expenses)
- **Total Evaluations**: ~35 (class feedback and ratings)

## 🎉 Ready for Testing!

Your SWEAT24 gym management system now has comprehensive, realistic data for testing all features:

✅ **User Management**: Registration, profiles, roles  
✅ **Package Management**: Sales, renewals, extensions  
✅ **Booking System**: Scheduling, cancellations, attendance  
✅ **Financial Management**: Payments, expenses, reporting  
✅ **Communication**: Notifications, campaigns, targeting  
✅ **Analytics**: Activity tracking, performance metrics  
✅ **Evaluations**: Feedback collection, satisfaction tracking  

Happy testing! 🚀