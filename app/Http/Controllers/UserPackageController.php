<?php

namespace App\Http\Controllers;

use App\Models\UserPackage;
use App\Models\User;
use App\Models\Package;
use App\Models\PackageHistory;
use App\Services\PackageNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserPackageController extends Controller
{
    protected $notificationService;

    public function __construct(PackageNotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Get all user packages with filters
     */
    public function index(Request $request)
    {
        $query = UserPackage::with(['user', 'package']);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by user
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter expiring soon
        if ($request->boolean('expiring_soon')) {
            $query->whereDate('expiry_date', '<=', now()->addDays(7))
                  ->whereDate('expiry_date', '>', now())
                  ->where('status', '!=', UserPackage::STATUS_EXPIRED);
        }

        // Sort by expiry date
        $query->orderBy('expiry_date', 'asc');

        return response()->json($query->paginate(20));
    }

    /**
     * Get user's packages
     */
    public function userPackages($userId)
    {
        $packages = UserPackage::where('user_id', $userId)
            ->with(['package', 'history', 'renewals'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($packages);
    }

    /**
     * Show package details with history
     */
    public function show(UserPackage $userPackage)
    {
        $userPackage->load(['user', 'package', 'history.performedBy', 'notificationLogs']);
        
        return response()->json([
            'package' => $userPackage,
            'stats' => [
                'days_until_expiry' => $userPackage->getDaysUntilExpiry(),
                'usage_percentage' => $userPackage->total_sessions > 0 
                    ? round((($userPackage->total_sessions - $userPackage->remaining_sessions) / $userPackage->total_sessions) * 100, 2)
                    : 0,
                'can_be_used' => $userPackage->canBeUsed(),
            ],
        ]);
    }

    /**
     * Assign package to user
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'package_id' => 'required|exists:packages,id',
            'expiry_date' => 'nullable|date|after:today',
            'auto_renew' => 'boolean',
        ]);

        $package = Package::findOrFail($validated['package_id']);
        $user = User::findOrFail($validated['user_id']);

        $userPackage = null;
        
        DB::transaction(function () use ($package, $user, $validated, &$userPackage) {
            $userPackage = UserPackage::create([
                'user_id' => $user->id,
                'package_id' => $package->id,
                'name' => $package->name,
                'assigned_date' => now(),
                'expiry_date' => $validated['expiry_date'] ?? now()->addDays($package->duration),
                'remaining_sessions' => $package->sessions,
                'total_sessions' => $package->sessions,
                'status' => UserPackage::STATUS_ACTIVE,
                'auto_renew' => $validated['auto_renew'] ?? false,
            ]);

            // Log the purchase
            $userPackage->logHistory('purchased', [
                'price' => $package->price,
                'auto_renew' => $userPackage->auto_renew,
            ]);

            // Record payment in cash register
            \App\Models\CashRegisterEntry::create([
                'type' => 'income',
                'amount' => $package->price,
                'description' => "Πληρωμή πακέτου: {$package->name} - {$user->name}",
                'category' => 'Package Payment',
                'user_id' => auth()->id() ?? 1, // Who recorded the payment
                'payment_method' => $request->get('payment_method', 'cash'), // Default to cash if not specified
                'related_entity_id' => $user->id,
                'related_entity_type' => 'customer',
            ]);

            // Send welcome notification
            $this->notificationService->sendPackagePurchaseNotification($userPackage);
        });

        return response()->json([
            'message' => 'Package assigned successfully',
            'user_package' => $userPackage->load(['user', 'package']),
        ], 201);
    }

    /**
     * Freeze a package
     */
    public function freeze(Request $request, UserPackage $userPackage)
    {
        if ($userPackage->is_frozen) {
            return response()->json(['message' => 'Package is already frozen'], 422);
        }

        $validated = $request->validate([
            'duration_days' => 'nullable|integer|min:1|max:90',
        ]);

        $userPackage->freeze($validated['duration_days'] ?? null);

        return response()->json([
            'message' => 'Package frozen successfully',
            'user_package' => $userPackage,
        ]);
    }

    /**
     * Unfreeze a package
     */
    public function unfreeze(UserPackage $userPackage)
    {
        if (!$userPackage->is_frozen) {
            return response()->json(['message' => 'Package is not frozen'], 422);
        }

        $userPackage->unfreeze();

        return response()->json([
            'message' => 'Package unfrozen successfully',
            'user_package' => $userPackage,
        ]);
    }

    /**
     * Renew a package
     */
    public function renew(Request $request, UserPackage $userPackage)
    {
        $validated = $request->validate([
            'package_id' => 'nullable|exists:packages,id',
            'additional_sessions' => 'nullable|integer|min:1',
        ]);

        $newPackage = $userPackage->renew(
            $validated['package_id'] ?? null,
            $validated['additional_sessions'] ?? null
        );

        if (!$newPackage) {
            return response()->json(['message' => 'Failed to renew package'], 422);
        }

        // Send renewal notification
        $this->notificationService->sendPackageRenewalNotification($newPackage);

        return response()->json([
            'message' => 'Package renewed successfully',
            'new_package' => $newPackage->load(['user', 'package']),
        ]);
    }

    /**
     * Update package settings
     */
    public function update(Request $request, UserPackage $userPackage)
    {
        $validated = $request->validate([
            'auto_renew' => 'boolean',
            'expiry_date' => 'date|after:today',
        ]);

        $userPackage->update($validated);

        return response()->json([
            'message' => 'Package updated successfully',
            'user_package' => $userPackage,
        ]);
    }

    /**
     * Get package statistics
     */
    public function statistics()
    {
        $stats = [
            'total_active' => UserPackage::where('status', UserPackage::STATUS_ACTIVE)->count(),
            'expiring_soon' => UserPackage::where('status', UserPackage::STATUS_EXPIRING_SOON)->count(),
            'expired' => UserPackage::where('status', UserPackage::STATUS_EXPIRED)->count(),
            'frozen' => UserPackage::where('status', UserPackage::STATUS_FROZEN)->count(),
            'auto_renew_enabled' => UserPackage::where('auto_renew', true)->count(),
            'expiring_this_week' => UserPackage::whereDate('expiry_date', '<=', now()->addDays(7))
                ->whereDate('expiry_date', '>', now())
                ->where('status', '!=', UserPackage::STATUS_EXPIRED)
                ->count(),
            'revenue_from_renewals' => PackageHistory::where('action', 'renewed')
                ->whereHas('userPackage.package')
                ->join('packages', 'user_packages.package_id', '=', 'packages.id')
                ->where('package_history.created_at', '>=', now()->startOfMonth())
                ->sum('packages.price'),
        ];

        return response()->json($stats);
    }

    /**
     * Get expiring packages report
     */
    public function expiringReport()
    {
        $packages = UserPackage::with(['user', 'package'])
            ->whereDate('expiry_date', '<=', now()->addDays(7))
            ->whereDate('expiry_date', '>', now())
            ->where('status', '!=', UserPackage::STATUS_EXPIRED)
            ->orderBy('expiry_date', 'asc')
            ->get()
            ->map(function ($package) {
                return [
                    'id' => $package->id,
                    'user' => [
                        'id' => $package->user->id,
                        'name' => $package->user->name,
                        'email' => $package->user->email,
                        'phone' => $package->user->phone_number,
                    ],
                    'package_name' => $package->name,
                    'days_until_expiry' => $package->getDaysUntilExpiry(),
                    'expiry_date' => $package->expiry_date->format('Y-m-d'),
                    'remaining_sessions' => $package->remaining_sessions,
                    'auto_renew' => $package->auto_renew,
                    'last_notification' => $package->last_notification_sent_at?->format('Y-m-d H:i:s'),
                ];
            });

        return response()->json($packages);
    }

    /**
     * Send manual expiry notification
     */
    public function sendExpiryNotification(UserPackage $userPackage)
    {
        if (!$userPackage->isExpiringSoon() && !$userPackage->isExpired()) {
            return response()->json(['message' => 'Package is not expiring soon'], 422);
        }

        $sent = $this->notificationService->sendExpiryNotification($userPackage);

        if ($sent) {
            return response()->json(['message' => 'Notification sent successfully']);
        }

        return response()->json(['message' => 'Failed to send notification'], 500);
    }
}