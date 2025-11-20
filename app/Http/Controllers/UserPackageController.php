<?php

namespace App\Http\Controllers;

use App\Models\UserPackage;
use App\Models\User;
use App\Models\Package;
use App\Models\PackageHistory;
use App\Models\PaymentInstallment;
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
            'special_price' => 'nullable|numeric|min:0',
            'special_price_reason' => 'nullable|string|max:500',
        ]);

        $package = Package::findOrFail($validated['package_id']);
        $user = User::findOrFail($validated['user_id']);

        // Determine the final price
        $hasSpecialPrice = isset($validated['special_price']) && $validated['special_price'] !== $package->price;
        $finalPrice = $hasSpecialPrice ? $validated['special_price'] : $package->price;

        $userPackage = null;

        DB::transaction(function () use ($package, $user, $validated, $finalPrice, $hasSpecialPrice, $request, &$userPackage) {
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
                'custom_price' => $hasSpecialPrice ? $finalPrice : null,
                'assigned_by' => auth()->user()->name ?? 'System',
            ]);

            // Log the purchase
            $userPackage->logHistory('purchased', [
                'price' => $finalPrice,
                'original_price' => $package->price,
                'special_price' => $hasSpecialPrice,
                'special_price_reason' => $validated['special_price_reason'] ?? null,
                'auto_renew' => $userPackage->auto_renew,
            ]);

            // Record payment in cash register
            \App\Models\CashRegisterEntry::create([
                'type' => 'income',
                'amount' => $finalPrice,
                'description' => "Πληρωμή πακέτου: {$package->name} - {$user->name}" .
                    ($hasSpecialPrice ? " (Ειδική τιμή: €{$finalPrice}, Κανονική: €{$package->price})" : ''),
                'category' => 'Package Payment',
                'user_id' => auth()->id() ?? 1,
                'payment_method' => $request->get('payment_method', 'cash'),
                'related_entity_id' => $user->id,
                'related_entity_type' => 'customer',
            ]);

            // Send notification to admin if special price was applied
            if ($hasSpecialPrice) {
                $this->notifyAdminAboutSpecialPrice($userPackage, $package, $user, $finalPrice, $validated['special_price_reason'] ?? null);
            }

            // Send welcome notification to user
            $this->notificationService->sendPackagePurchaseNotification($userPackage);
        });

        // Dispatch payment event for loyalty points (outside transaction)
        \App\Events\PaymentProcessed::dispatch(
            $user,
            $finalPrice,
            "Πληρωμή πακέτου: {$package->name}",
            $userPackage,
            $request->get('payment_method', 'cash')
        );

        return response()->json([
            'message' => 'Package assigned successfully',
            'user_package' => $userPackage->load(['user', 'package']),
            'special_price_applied' => $hasSpecialPrice,
        ], 201);
    }

    /**
     * Notify admin about special price assignment
     */
    protected function notifyAdminAboutSpecialPrice($userPackage, $package, $user, $specialPrice, $reason = null)
    {
        // Get all admin users
        $admins = User::where('role', 'admin')->get();

        foreach ($admins as $admin) {
            // Create owner notification
            \App\Models\OwnerNotification::create([
                'title' => '⚠️ Ειδική Τιμή Πακέτου',
                'message' => sprintf(
                    "Το πακέτο '%s' ανατέθηκε στον χρήστη %s με ειδική τιμή €%.2f (Κανονική τιμή: €%.2f)\n\nΑνατέθηκε από: %s\n%s",
                    $package->name,
                    $user->name,
                    $specialPrice,
                    $package->price,
                    auth()->user()->name ?? 'System',
                    $reason ? "Λόγος: {$reason}" : ''
                ),
                'type' => 'special_price',
                'priority' => 'high',
                'user_id' => $admin->id,
                'related_model_type' => 'UserPackage',
                'related_model_id' => $userPackage->id,
                'metadata' => json_encode([
                    'user_package_id' => $userPackage->id,
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'package_id' => $package->id,
                    'package_name' => $package->name,
                    'original_price' => $package->price,
                    'special_price' => $specialPrice,
                    'discount_amount' => $package->price - $specialPrice,
                    'discount_percentage' => round((($package->price - $specialPrice) / $package->price) * 100, 2),
                    'reason' => $reason,
                    'assigned_by' => auth()->user()->name ?? 'System',
                    'assigned_by_id' => auth()->id(),
                ]),
            ]);
        }
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

    /**
     * Extend package expiry date
     */
    public function extend(Request $request, $userPackageId)
    {
        $validated = $request->validate([
            'new_expiry_date' => 'required|date|after:today',
            'extension_notes' => 'nullable|string|max:1000',
        ]);

        $userPackage = UserPackage::with(['user', 'package'])->findOrFail($userPackageId);

        $oldExpiryDate = $userPackage->expiry_date;
        $newExpiryDate = $validated['new_expiry_date'];

        // Update expiry date and extension tracking
        $userPackage->expiry_date = $newExpiryDate;
        $userPackage->extension_from = $oldExpiryDate;
        $userPackage->extension_to = $newExpiryDate;
        $userPackage->extension_notes = $validated['extension_notes'] ?? null;
        $userPackage->save();

        // Calculate days extended
        $daysExtended = 0;
        if ($oldExpiryDate) {
            $oldDate = new \DateTime($oldExpiryDate);
            $newDate = new \DateTime($newExpiryDate);
            $daysExtended = $oldDate->diff($newDate)->days;
        }

        // Send notification to admins
        $admins = User::where('role', 'admin')->get();
        foreach ($admins as $admin) {
            $message = sprintf(
                "Το πακέτο '%s' του χρήστη %s επεκτάθηκε μέχρι %s\n\nΠαράταση από: %s έως %s\nΕπεκτάθηκε από: %s\nΗμέρες επέκτασης: %d",
                $userPackage->package->name ?? 'Άγνωστο Πακέτο',
                $userPackage->user->name,
                \Carbon\Carbon::parse($newExpiryDate)->format('d/m/Y'),
                \Carbon\Carbon::parse($oldExpiryDate)->format('d/m/Y'),
                \Carbon\Carbon::parse($newExpiryDate)->format('d/m/Y'),
                auth()->user()->name ?? 'System',
                $daysExtended
            );

            if (!empty($validated['extension_notes'])) {
                $message .= "\n\nΑιτιολογία: " . $validated['extension_notes'];
            }

            \App\Models\OwnerNotification::create([
                'title' => 'Επέκταση Πακέτου',
                'message' => $message,
                'type' => 'package_extension',
                'priority' => 'medium',
                'user_id' => null, // null = all admins
                'customer_name' => $userPackage->user->name,
                'package_id' => $userPackage->id,
                'is_read' => false,
                'metadata' => json_encode([
                    'package_id' => $userPackage->id,
                    'package_name' => $userPackage->package->name ?? null,
                    'customer_name' => $userPackage->user->name,
                    'customer_id' => $userPackage->user_id,
                    'old_expiry_date' => $oldExpiryDate,
                    'new_expiry_date' => $newExpiryDate,
                    'days_extended' => $daysExtended,
                    'extended_by' => auth()->user()->name ?? 'System',
                ]),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Το πακέτο επεκτάθηκε επιτυχώς',
            'data' => $userPackage->fresh(['user', 'package'])
        ]);
    }

    /**
     * Toggle package pause status
     */
    public function togglePause(Request $request, $userPackageId)
    {
        $validated = $request->validate([
            'pause_reason' => 'nullable|string|max:1000',
        ]);

        $userPackage = UserPackage::with(['user', 'package'])->findOrFail($userPackageId);

        $isPausing = $userPackage->status !== 'paused';

        // Toggle status
        if ($isPausing) {
            $userPackage->status = 'paused';
            $userPackage->pause_reason = $validated['pause_reason'] ?? null;
        } else {
            $userPackage->status = 'active';
            // Clear pause reason when activating
            $userPackage->pause_reason = null;
        }

        $userPackage->save();

        // Log the status change
        $userPackage->logHistory($isPausing ? 'paused' : 'activated', [
            'reason' => $validated['pause_reason'] ?? null,
            'changed_by' => auth()->user()->name ?? 'System',
            'changed_by_id' => auth()->id(),
        ]);

        // Send notification to admins if pausing with reason
        if ($isPausing && !empty($validated['pause_reason'])) {
            $admins = User::where('role', 'admin')->get();
            foreach ($admins as $admin) {
                \App\Models\OwnerNotification::create([
                    'title' => 'Παύση Πακέτου',
                    'message' => sprintf(
                        "Το πακέτο '%s' του χρήστη %s τέθηκε σε παύση\n\nΛόγος διακοπής: %s\n\nΈγινε από: %s",
                        $userPackage->package->name ?? 'Άγνωστο Πακέτο',
                        $userPackage->user->name,
                        $validated['pause_reason'],
                        auth()->user()->name ?? 'System'
                    ),
                    'type' => 'package_pause',
                    'priority' => 'low',
                    'user_id' => null,
                    'customer_name' => $userPackage->user->name,
                    'package_id' => $userPackage->id,
                    'is_read' => false,
                    'metadata' => json_encode([
                        'package_id' => $userPackage->id,
                        'package_name' => $userPackage->package->name ?? null,
                        'customer_name' => $userPackage->user->name,
                        'customer_id' => $userPackage->user_id,
                        'pause_reason' => $validated['pause_reason'],
                        'paused_by' => auth()->user()->name ?? 'System',
                    ]),
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => $isPausing ? 'Το πακέτο τέθηκε σε παύση' : 'Το πακέτο ενεργοποιήθηκε',
            'data' => $userPackage->fresh(['user', 'package'])
        ]);
    }

    /**
     * Get authenticated user's partial payment summary
     * For mobile app - shows packages with pending payments
     */
    public function myPartialPayments()
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Get all packages with partial or pending payments
        $packages = UserPackage::where('user_id', $user->id)
            ->where(function ($query) {
                $query->where('payment_status', UserPackage::PAYMENT_STATUS_PARTIAL)
                    ->orWhere('payment_status', UserPackage::PAYMENT_STATUS_PENDING)
                    ->orWhere('amount_remaining', '>', 0);
            })
            ->with(['package'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Get all pending installments for the user
        $pendingInstallments = PaymentInstallment::where('customer_id', $user->id)
            ->whereIn('status', ['pending', 'overdue'])
            ->orderBy('due_date', 'asc')
            ->get();

        $result = [
            'has_pending_payments' => $packages->count() > 0 || $pendingInstallments->count() > 0,
            'total_amount_remaining' => 0,
            'total_pending_installments' => $pendingInstallments->count(),
            'next_installment_due' => null,
            'packages' => [],
            'upcoming_installments' => [],
        ];

        // Process packages with partial payments
        foreach ($packages as $package) {
            $totalAmount = $package->getEffectivePrice();
            $amountPaid = $package->amount_paid ?? 0;
            $amountRemaining = $package->amount_remaining ?? ($totalAmount - $amountPaid);

            $result['total_amount_remaining'] += $amountRemaining;

            // Get installments for this package
            $packageInstallments = $pendingInstallments->where('package_id', $package->package_id);
            $paidInstallments = PaymentInstallment::where('customer_id', $user->id)
                ->where('package_id', $package->package_id)
                ->where('status', 'paid')
                ->count();
            $totalInstallments = $package->installments ?? ($paidInstallments + $packageInstallments->count());

            $result['packages'][] = [
                'user_package_id' => $package->id,
                'package_name' => $package->name,
                'package_id' => $package->package_id,
                'total_amount' => $totalAmount,
                'amount_paid' => $amountPaid,
                'amount_remaining' => $amountRemaining,
                'payment_status' => $package->payment_status,
                'payment_method' => $package->payment_method,
                'is_custom_package' => $package->is_custom_package,
                'assigned_date' => $package->assigned_date?->format('Y-m-d'),
                'expiry_date' => $package->expiry_date?->format('Y-m-d'),
                'installments' => [
                    'total' => $totalInstallments,
                    'paid' => $paidInstallments,
                    'remaining' => $packageInstallments->count(),
                ],
                'remaining_sessions' => $package->remaining_sessions,
                'status' => $package->status,
            ];
        }

        // Process upcoming installments
        foreach ($pendingInstallments as $installment) {
            $isOverdue = $installment->due_date && $installment->due_date->isPast();

            $result['upcoming_installments'][] = [
                'id' => $installment->id,
                'package_id' => $installment->package_id,
                'package_name' => $installment->package_name,
                'installment_number' => $installment->installment_number,
                'total_installments' => $installment->total_installments,
                'amount' => $installment->amount,
                'due_date' => $installment->due_date?->format('Y-m-d'),
                'status' => $installment->status,
                'is_overdue' => $isOverdue,
                'days_until_due' => $installment->due_date ? now()->diffInDays($installment->due_date, false) : null,
            ];

            // Set next installment due
            if (!$result['next_installment_due'] && !$isOverdue && $installment->due_date) {
                $result['next_installment_due'] = [
                    'date' => $installment->due_date->format('Y-m-d'),
                    'amount' => $installment->amount,
                    'package_name' => $installment->package_name,
                    'installment_number' => $installment->installment_number,
                    'total_installments' => $installment->total_installments,
                ];
            }
        }

        // Sort upcoming installments: overdue first, then by due date
        usort($result['upcoming_installments'], function ($a, $b) {
            if ($a['is_overdue'] && !$b['is_overdue']) return -1;
            if (!$a['is_overdue'] && $b['is_overdue']) return 1;
            return strcmp($a['due_date'] ?? '', $b['due_date'] ?? '');
        });

        return response()->json($result);
    }

    /**
     * Get partial payment summary for a specific user (admin use)
     */
    public function userPartialPayments($userId)
    {
        $user = User::findOrFail($userId);

        // Get all packages with partial or pending payments
        $packages = UserPackage::where('user_id', $userId)
            ->where(function ($query) {
                $query->where('payment_status', UserPackage::PAYMENT_STATUS_PARTIAL)
                    ->orWhere('payment_status', UserPackage::PAYMENT_STATUS_PENDING)
                    ->orWhere('amount_remaining', '>', 0);
            })
            ->with(['package'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Get all pending installments for the user
        $pendingInstallments = PaymentInstallment::where('customer_id', $userId)
            ->whereIn('status', ['pending', 'overdue'])
            ->orderBy('due_date', 'asc')
            ->get();

        $result = [
            'user_id' => (int) $userId,
            'user_name' => $user->name,
            'has_pending_payments' => $packages->count() > 0 || $pendingInstallments->count() > 0,
            'total_amount_remaining' => 0,
            'total_pending_installments' => $pendingInstallments->count(),
            'packages' => [],
            'upcoming_installments' => [],
        ];

        // Process packages
        foreach ($packages as $package) {
            $totalAmount = $package->getEffectivePrice();
            $amountPaid = $package->amount_paid ?? 0;
            $amountRemaining = $package->amount_remaining ?? ($totalAmount - $amountPaid);

            $result['total_amount_remaining'] += $amountRemaining;

            $packageInstallments = $pendingInstallments->where('package_id', $package->package_id);
            $paidInstallments = PaymentInstallment::where('customer_id', $userId)
                ->where('package_id', $package->package_id)
                ->where('status', 'paid')
                ->count();

            $result['packages'][] = [
                'user_package_id' => $package->id,
                'package_name' => $package->name,
                'total_amount' => $totalAmount,
                'amount_paid' => $amountPaid,
                'amount_remaining' => $amountRemaining,
                'payment_status' => $package->payment_status,
                'installments_paid' => $paidInstallments,
                'installments_remaining' => $packageInstallments->count(),
                'assigned_date' => $package->assigned_date?->format('Y-m-d'),
            ];
        }

        // Process installments
        foreach ($pendingInstallments as $installment) {
            $result['upcoming_installments'][] = [
                'id' => $installment->id,
                'package_name' => $installment->package_name,
                'installment_number' => $installment->installment_number,
                'total_installments' => $installment->total_installments,
                'amount' => $installment->amount,
                'due_date' => $installment->due_date?->format('Y-m-d'),
                'status' => $installment->status,
                'is_overdue' => $installment->due_date && $installment->due_date->isPast(),
            ];
        }

        return response()->json($result);
    }
}