<?php

namespace App\Http\Controllers;

use App\Models\UserPackage;
use App\Models\User;
use App\Models\Package;
use App\Models\PackageHistory;
use App\Services\PackageNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminPackageController extends Controller
{
    protected $notificationService;

    public function __construct(PackageNotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Display package management dashboard
     */
    public function index(Request $request)
    {
        $query = UserPackage::with(['user', 'package']);

        // Apply filters
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->boolean('expiring_soon')) {
            $query->whereDate('expiry_date', '<=', now()->addDays(7))
                  ->whereDate('expiry_date', '>', now())
                  ->where('status', '!=', UserPackage::STATUS_EXPIRED);
        }

        $userPackages = $query->orderBy('expiry_date', 'asc')->paginate(20);

        // Get statistics
        $stats = [
            'total_active' => UserPackage::where('status', UserPackage::STATUS_ACTIVE)->count(),
            'expiring_soon' => UserPackage::where('status', UserPackage::STATUS_EXPIRING_SOON)->count(),
            'expired' => UserPackage::where('status', UserPackage::STATUS_EXPIRED)->count(),
            'frozen' => UserPackage::where('status', UserPackage::STATUS_FROZEN)->count(),
            'auto_renew_enabled' => UserPackage::where('auto_renew', true)->count(),
        ];

        $packages = Package::where('status', 'active')->get();
        $users = User::where('status', 'active')->orderBy('name')->get();

        return view('admin.packages.index', compact('userPackages', 'stats', 'packages', 'users'));
    }

    /**
     * Show package details
     */
    public function show(UserPackage $userPackage)
    {
        $userPackage->load(['user', 'package', 'history.performedBy', 'notificationLogs']);
        
        $stats = [
            'days_until_expiry' => $userPackage->getDaysUntilExpiry(),
            'usage_percentage' => $userPackage->total_sessions > 0 
                ? round((($userPackage->total_sessions - $userPackage->remaining_sessions) / $userPackage->total_sessions) * 100, 2)
                : 0,
            'can_be_used' => $userPackage->canBeUsed(),
        ];

        return view('admin.packages.show', compact('userPackage', 'stats'));
    }

    /**
     * Show form to assign new package
     */
    public function create()
    {
        $users = User::where('status', 'active')->orderBy('name')->get();
        $packages = Package::where('status', 'active')->get();
        
        return view('admin.packages.create', compact('users', 'packages'));
    }

    /**
     * Store new package assignment
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

            // Send welcome notification
            $this->notificationService->sendPackagePurchaseNotification($userPackage);
        });

        return redirect()->route('admin.packages.index')
            ->with('success', 'Package assigned successfully!');
    }

    /**
     * Freeze a package
     */
    public function freeze(Request $request, UserPackage $userPackage)
    {
        $validated = $request->validate([
            'duration_days' => 'nullable|integer|min:1|max:90',
        ]);

        $userPackage->freeze($validated['duration_days'] ?? null);

        return redirect()->back()
            ->with('success', 'Package frozen successfully!');
    }

    /**
     * Unfreeze a package
     */
    public function unfreeze(UserPackage $userPackage)
    {
        $userPackage->unfreeze();

        return redirect()->back()
            ->with('success', 'Package unfrozen successfully!');
    }

    /**
     * Show renewal form
     */
    public function showRenewal(UserPackage $userPackage)
    {
        $packages = Package::where('status', 'active')->get();
        return view('admin.packages.renew', compact('userPackage', 'packages'));
    }

    /**
     * Process renewal
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
            return redirect()->back()
                ->with('error', 'Failed to renew package');
        }

        // Send renewal notification
        $this->notificationService->sendPackageRenewalNotification($newPackage);

        return redirect()->route('admin.packages.show', $newPackage)
            ->with('success', 'Package renewed successfully!');
    }

    /**
     * Update package settings
     */
    public function update(Request $request, UserPackage $userPackage)
    {
        $validated = $request->validate([
            'auto_renew' => 'boolean',
            'expiry_date' => 'date|after:today',
            'remaining_sessions' => 'integer|min:0',
        ]);

        $userPackage->update($validated);

        return redirect()->back()
            ->with('success', 'Package updated successfully!');
    }

    /**
     * Show expiring packages report
     */
    public function expiringReport()
    {
        $packages = UserPackage::with(['user', 'package'])
            ->whereDate('expiry_date', '<=', now()->addDays(7))
            ->whereDate('expiry_date', '>', now())
            ->where('status', '!=', UserPackage::STATUS_EXPIRED)
            ->orderBy('expiry_date', 'asc')
            ->get();

        return view('admin.packages.expiring', compact('packages'));
    }

    /**
     * Send manual notification
     */
    public function sendNotification(UserPackage $userPackage)
    {
        $sent = $this->notificationService->sendExpiryNotification($userPackage);

        if ($sent) {
            return redirect()->back()
                ->with('success', 'Notification sent successfully!');
        }

        return redirect()->back()
            ->with('error', 'Failed to send notification');
    }
}