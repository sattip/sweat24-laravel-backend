<?php

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group.
|
| Routes are organized into domain-specific files for better maintainability:
| - auth.php: Authentication routes (login, register, password reset)
| - users.php: User and profile management
| - bookings.php: Booking and waitlist management
| - classes.php: Classes and instructors
| - packages.php: Packages and user packages
| - services.php: Services and stores
| - financial.php: Financial routes (payments, cash register, expenses)
| - loyalty.php: Loyalty, referrals, and points
| - notifications.php: Notifications and filters
| - fitness.php: Fitness tracking (measurements, progress, wellness)
| - admin.php: Admin-specific routes (tasks, analytics, management)
| - misc.php: Miscellaneous routes (orders, chat, dashboard, debug)
|
*/

// Load domain-specific route files
require __DIR__ . '/api/auth.php';
require __DIR__ . '/api/users.php';
require __DIR__ . '/api/bookings.php';
require __DIR__ . '/api/classes.php';
require __DIR__ . '/api/packages.php';
require __DIR__ . '/api/services.php';
require __DIR__ . '/api/financial.php';
require __DIR__ . '/api/loyalty.php';
require __DIR__ . '/api/notifications.php';
require __DIR__ . '/api/fitness.php';
require __DIR__ . '/api/admin.php';
require __DIR__ . '/api/misc.php';
