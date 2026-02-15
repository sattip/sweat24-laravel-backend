<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\User;
use App\Models\UserPackage;
use App\Models\Package;
use App\Models\GymClass;
use App\Models\Booking;
use App\Models\Instructor;
use App\Services\NotificationService;
use App\Services\PackageNotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DevToolsController extends Controller
{
    protected $notificationService;
    protected $packageNotificationService;

    public function __construct(NotificationService $notificationService, PackageNotificationService $packageNotificationService)
    {
        $this->notificationService = $notificationService;
        $this->packageNotificationService = $packageNotificationService;
        
        // Only allow in development mode
        if (config('app.env') !== 'local') {
            abort(404, 'Development tools only available in local environment');
        }
    }

    /**
     * Show the development tools page
     */
    public function index()
    {
        return view('admin.settings.test');
    }

    /**
     * Generate sample data for testing
     */
    public function generateSampleData(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'users_count' => 'nullable|integer|min:1|max:100',
            'notifications_count' => 'nullable|integer|min:1|max:50',
            'packages_count' => 'nullable|integer|min:1|max:20',
            'classes_count' => 'nullable|integer|min:1|max:20',
        ]);

        $usersCount = $validated['users_count'] ?? 10;
        $notificationsCount = $validated['notifications_count'] ?? 5;
        $packagesCount = $validated['packages_count'] ?? 5;
        $classesCount = $validated['classes_count'] ?? 5;

        $created = [];

        try {
            DB::transaction(function () use ($usersCount, $notificationsCount, $packagesCount, $classesCount, &$created) {
                // Create sample users
                for ($i = 1; $i <= $usersCount; $i++) {
                    User::create([
                        'name' => "Test User {$i}",
                        'email' => "testuser{$i}@example.com",
                        'password' => bcrypt('password'),
                        'membership_type' => 'Member',
                        'status' => 'active',
                        'phone' => '+1234567890' . $i,
                        'address' => "123 Test Street {$i}",
                    ]);
                }
                $created['users'] = $usersCount;

                // Create sample packages
                for ($i = 1; $i <= $packagesCount; $i++) {
                    Package::create([
                        'name' => "Test Package {$i}",
                        'description' => "Test package description {$i}",
                        'price' => 50.00 + ($i * 10),
                        'duration_days' => 30,
                        'class_limit' => 10 + $i,
                        'status' => 'active',
                    ]);
                }
                $created['packages'] = $packagesCount;

                // Create sample instructors
                for ($i = 1; $i <= 3; $i++) {
                    Instructor::create([
                        'name' => "Test Instructor {$i}",
                        'email' => "instructor{$i}@example.com",
                        'specialization' => 'Fitness',
                        'bio' => "Test instructor bio {$i}",
                        'hourly_rate' => 25.00 + ($i * 5),
                        'status' => 'active',
                    ]);
                }
                $created['instructors'] = 3;

                // Create sample classes
                $instructors = Instructor::all();
                for ($i = 1; $i <= $classesCount; $i++) {
                    GymClass::create([
                        'name' => "Test Class {$i}",
                        'description' => "Test class description {$i}",
                        'instructor_id' => $instructors->random()->id,
                        'start_time' => now()->addDays($i)->addHours(10),
                        'end_time' => now()->addDays($i)->addHours(11),
                        'capacity' => 15,
                        'price' => 20.00,
                        'status' => 'active',
                    ]);
                }
                $created['classes'] = $classesCount;

                // Create sample notifications
                for ($i = 1; $i <= $notificationsCount; $i++) {
                    $this->notificationService->createNotification([
                        'title' => "[SAMPLE] Test Notification {$i}",
                        'message' => "This is a sample notification {$i} created for testing purposes at " . now()->format('Y-m-d H:i:s'),
                        'type' => ['info', 'warning', 'success'][($i - 1) % 3],
                        'priority' => ['low', 'medium', 'high'][($i - 1) % 3],
                        'channels' => ['in_app'],
                        'filters' => [],
                        'status' => $i % 2 === 0 ? 'sent' : 'draft'
                    ]);
                }
                $created['notifications'] = $notificationsCount;
            });

            Log::info('Sample data generated successfully', $created);

            return response()->json([
                'message' => 'Sample data generated successfully',
                'created' => $created
            ]);

        } catch (\Exception $e) {
            Log::error('Error generating sample data', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Failed to generate sample data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clear all notifications
     */
    public function clearNotifications(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'nullable|in:all,test,sent,draft'
        ]);

        $type = $validated['type'] ?? 'test';

        try {
            $query = Notification::query();

            switch ($type) {
                case 'test':
                    $query->where(function ($q) {
                        $q->where('title', 'like', '%[TEST]%')
                          ->orWhere('title', 'like', '%[SAMPLE]%')
                          ->orWhere('title', 'like', '%[BULK TEST]%')
                          ->orWhere('title', 'like', '%[TARGETED TEST]%');
                    });
                    break;
                case 'sent':
                    $query->where('status', 'sent');
                    break;
                case 'draft':
                    $query->where('status', 'draft');
                    break;
                case 'all':
                    // Clear all notifications
                    break;
            }

            $deletedCount = $query->delete();

            Log::info('Notifications cleared', [
                'type' => $type,
                'deleted_count' => $deletedCount
            ]);

            return response()->json([
                'message' => "Cleared {$deletedCount} notifications of type '{$type}'",
                'deleted_count' => $deletedCount,
                'type' => $type
            ]);

        } catch (\Exception $e) {
            Log::error('Error clearing notifications', [
                'error' => $e->getMessage(),
                'type' => $type
            ]);

            return response()->json([
                'error' => 'Failed to clear notifications: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reset user states for testing
     */
    public function resetUserStates(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'action' => 'required|in:mark_all_read,mark_all_unread,reset_activity,activate_all'
        ]);

        $action = $validated['action'];

        try {
            $affectedCount = 0;

            switch ($action) {
                case 'mark_all_read':
                    $affectedCount = DB::table('notification_recipients')
                        ->whereNull('read_at')
                        ->update(['read_at' => now()]);
                    break;

                case 'mark_all_unread':
                    $affectedCount = DB::table('notification_recipients')
                        ->whereNotNull('read_at')
                        ->update(['read_at' => null]);
                    break;

                case 'reset_activity':
                    $affectedCount = DB::table('activity_logs')->delete();
                    break;

                case 'activate_all':
                    $affectedCount = User::where('status', '!=', 'active')
                        ->update(['status' => 'active']);
                    break;
            }

            Log::info('User states reset', [
                'action' => $action,
                'affected_count' => $affectedCount
            ]);

            return response()->json([
                'message' => "Reset action '{$action}' completed successfully",
                'affected_count' => $affectedCount,
                'action' => $action
            ]);

        } catch (\Exception $e) {
            Log::error('Error resetting user states', [
                'error' => $e->getMessage(),
                'action' => $action
            ]);

            return response()->json([
                'error' => 'Failed to reset user states: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Trigger package expiry notifications for testing
     */
    public function triggerPackageExpiry(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'days_ahead' => 'nullable|integer|min:1|max:30',
            'simulate_expiry' => 'nullable|boolean'
        ]);

        $daysAhead = $validated['days_ahead'] ?? 7;
        $simulateExpiry = $validated['simulate_expiry'] ?? false;

        try {
            $results = [];

            if ($simulateExpiry) {
                // Create some test user packages that are about to expire
                $users = User::where('membership_type', 'Member')->take(3)->get();
                $packages = Package::where('status', 'active')->take(2)->get();

                foreach ($users as $user) {
                    foreach ($packages as $package) {
                        UserPackage::create([
                            'user_id' => $user->id,
                            'package_id' => $package->id,
                            'price_paid' => $package->price,
                            'purchase_date' => now()->subDays($package->duration_days - $daysAhead),
                            'expiry_date' => now()->addDays($daysAhead),
                            'status' => 'active',
                            'classes_remaining' => $package->class_limit - 2,
                        ]);
                    }
                }
                $results['simulated_packages'] = $users->count() * $packages->count();
            }

            // Send expiry notifications
            $expiringPackages = UserPackage::with(['user', 'package'])
                ->where('status', 'active')
                ->where('expiry_date', '<=', now()->addDays($daysAhead))
                ->where('expiry_date', '>', now())
                ->get();

            $notificationsSent = 0;
            foreach ($expiringPackages as $userPackage) {
                $this->packageNotificationService->sendExpiryNotification($userPackage);
                $notificationsSent++;
            }

            $results['expiring_packages_found'] = $expiringPackages->count();
            $results['notifications_sent'] = $notificationsSent;

            Log::info('Package expiry notifications triggered', [
                'days_ahead' => $daysAhead,
                'simulate_expiry' => $simulateExpiry,
                'results' => $results
            ]);

            return response()->json([
                'message' => 'Package expiry notifications triggered successfully',
                'results' => $results
            ]);

        } catch (\Exception $e) {
            Log::error('Error triggering package expiry notifications', [
                'error' => $e->getMessage(),
                'days_ahead' => $daysAhead,
                'simulate_expiry' => $simulateExpiry
            ]);

            return response()->json([
                'error' => 'Failed to trigger package expiry notifications: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get system statistics for development
     */
    public function getStats(): JsonResponse
    {
        $stats = [
            'users' => [
                'total' => User::count(),
                'active' => User::where('status', 'active')->count(),
                'members' => User::where('membership_type', 'Member')->count(),
                'admins' => User::where('membership_type', 'Admin')->count(),
            ],
            'notifications' => [
                'total' => Notification::count(),
                'sent' => Notification::where('status', 'sent')->count(),
                'draft' => Notification::where('status', 'draft')->count(),
                'test' => Notification::where('title', 'like', '%[TEST]%')->count(),
            ],
            'packages' => [
                'total' => Package::count(),
                'active' => Package::where('status', 'active')->count(),
                'user_packages' => UserPackage::count(),
                'expiring_soon' => UserPackage::where('expiry_date', '<=', now()->addDays(7))->count(),
            ],
            'classes' => [
                'total' => GymClass::count(),
                'active' => GymClass::where('status', 'active')->count(),
                'bookings' => Booking::count(),
            ]
        ];

        return response()->json(['stats' => $stats]);
    }
}