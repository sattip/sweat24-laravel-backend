<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\User;
use App\Models\UserPackage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserPackageController extends Controller
{
    /**
     * Assign a package to a user (Admin only)
     */
    public function assign(Request $request, User $user)
    {
        $validated = $request->validate([
            'package_id' => 'required|exists:packages,id',
            'starts_at' => 'nullable|date',
            'notes' => 'nullable|string',
            'special_price' => 'nullable|numeric|min:0',
            'special_price_reason' => 'nullable|string|max:500',
            // Payment fields
            'payment_method' => 'nullable|string|in:cash,card,bank_transfer,other,transfer,iris,cash_a',
            'payment_status' => 'nullable|string|in:pending,partial,fully_paid',
            'amount_paid' => 'nullable|numeric|min:0',
            'amount_remaining' => 'nullable|numeric|min:0',
            'installments' => 'nullable|integer|min:1',
            'installments_count' => 'nullable|integer|min:1',
            'installment_frequency' => 'nullable|string|in:weekly,monthly',
            'payment_notes' => 'nullable|string|max:1000',
        ]);

        $package = Package::where('id', $validated['package_id'])
            ->where('status', 'active')
            ->firstOrFail();

        // Enforce one active membership at a time
        if ($package->type === 'membership') {
            $hasActiveMembership = UserPackage::where('user_id', $user->id)
                ->where('status', UserPackage::STATUS_ACTIVE)
                ->whereHas('package', function ($q) {
                    $q->where('type', 'membership');
                })
                ->exists();

            if ($hasActiveMembership) {
                return response()->json([
                    'message' => 'User already has an active membership package',
                ], 409);
            }
        }

        $startsAt = isset($validated['starts_at']) ?
            \Carbon\Carbon::parse($validated['starts_at']) : now();

        $expiresAt = $package->duration ? $startsAt->copy()->addDays((int) $package->duration) : null;

        // Determine if special price is being used
        $hasSpecialPrice = isset($validated['special_price']) && $validated['special_price'] !== $package->price;
        $finalPrice = $hasSpecialPrice ? $validated['special_price'] : $package->price;

        $userPackage = null;
        DB::transaction(function () use ($user, $package, $startsAt, $expiresAt, $request, $validated, $hasSpecialPrice, $finalPrice, &$userPackage) {
            // Calculate installments_paid
            $installments = $validated['installments_count'] ?? $validated['installments'] ?? 1;
            $amountPaid = $validated['amount_paid'] ?? $finalPrice;
            $amountRemaining = $validated['amount_remaining'] ?? 0;
            $installmentsPaid = 0;

            if ($installments > 1) {
                $totalAmount = $amountPaid + $amountRemaining;
                $installmentAmount = $totalAmount / $installments;
                if ($installmentAmount > 0) {
                    $installmentsPaid = floor($amountPaid / $installmentAmount);
                }
            }

            $userPackage = UserPackage::create([
                'user_id' => $user->id,
                'package_id' => $package->id,
                'name' => $package->name,
                'assigned_date' => $startsAt,
                'expiry_date' => $expiresAt,
                'remaining_sessions' => $package->sessions,
                'total_sessions' => $package->sessions,
                'status' => UserPackage::STATUS_ACTIVE,
                'auto_renew' => false,
                'custom_price' => $hasSpecialPrice ? $finalPrice : null,
                'assigned_by' => auth()->user()->name ?? 'Admin',
                // Payment fields
                'payment_method' => $validated['payment_method'] ?? 'cash',
                'payment_status' => $validated['payment_status'] ?? 'fully_paid',
                'amount_paid' => $amountPaid,
                'amount_remaining' => $amountRemaining,
                'installments' => $installments,
                'installments_paid' => $installmentsPaid,
                'installment_frequency' => $validated['installment_frequency'] ?? null,
                'payment_notes' => $validated['payment_notes'] ?? null,
            ]);

            $userPackage->logHistory('assigned', [
                'price' => $finalPrice,
                'original_price' => $package->price,
                'special_price' => $hasSpecialPrice,
                'special_price_reason' => $validated['special_price_reason'] ?? null,
                'notes' => $request->input('notes'),
                'admin_id' => auth()->id(),
            ]);

            // Record payment in cash register (only the amount actually paid)
            $amountPaid = $validated['amount_paid'] ?? $finalPrice;
            if ($amountPaid > 0) {
                $paymentDescription = "Πληρωμή πακέτου: {$package->name} - {$user->name}";
                if ($hasSpecialPrice) {
                    $paymentDescription .= " (Ειδική τιμή: €{$finalPrice}, Κανονική: €{$package->price})";
                }
                if (isset($validated['amount_remaining']) && $validated['amount_remaining'] > 0) {
                    $paymentDescription .= " - Προκαταβολή: €{$amountPaid}, Υπόλοιπο: €{$validated['amount_remaining']}";
                }

                \App\Models\CashRegisterEntry::create([
                    'type' => 'income',
                    'amount' => $amountPaid,
                    'description' => $paymentDescription,
                    'category' => 'Package Payment',
                    'user_id' => auth()->id() ?? 1,
                    'payment_method' => $validated['payment_method'] ?? 'cash',
                    'related_entity_id' => $user->id,
                    'related_entity_type' => 'customer',
                ]);
            }

            // Send notification to admin if special price was applied
            if ($hasSpecialPrice) {
                $this->notifyAdminAboutSpecialPrice($userPackage, $package, $user, $finalPrice, $validated['special_price_reason'] ?? null);
            }
        });

        return response()->json([
            'message' => 'Package assigned successfully.',
            'data' => [
                'id' => $userPackage->id,
                'user_id' => $user->id,
                'package_id' => $package->id,
                'package_name' => $package->name,
                'assigned_at' => $userPackage->assigned_date?->toISOString(),
                'starts_at' => $userPackage->assigned_date?->toISOString(),
                'expires_at' => $userPackage->expiry_date?->toDateString(),
                'total_sessions' => $userPackage->total_sessions,
                'remaining_sessions' => $userPackage->remaining_sessions,
                'status' => $userPackage->status,
            ],
        ], 201);
    }

    /**
     * Hard delete a specific user package by id, scoped to the given user.
     */
    public function destroy(Request $request, int $userId, int $userPackageId)
    {
        $user = User::find($userId);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $userPackage = UserPackage::where('id', $userPackageId)
            ->where('user_id', $userId)
            ->first();

        if (!$userPackage) {
            return response()->json(['message' => 'User package not found'], 404);
        }

        $userPackage->delete(); // Hard delete; FKs are ON DELETE CASCADE for related tables

        return response()->json(['message' => 'User package deleted'], 200);
    }

    /**
     * Get all packages for a user (Admin only)
     */
    public function getUserPackages(Request $request, int $userId)
    {
        $user = User::find($userId);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $packages = UserPackage::where('user_id', $userId)
            ->with(['package.service'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($userPackage) {
                return [
                    'id' => $userPackage->id,
                    'package_name' => $userPackage->package->name,
                    'service_name' => $userPackage->package->service->name ?? 'N/A',
                    'status' => $userPackage->status,
                    'remaining_sessions' => $userPackage->remaining_sessions,
                    'total_sessions' => $userPackage->total_sessions,
                    'assigned_date' => $userPackage->assigned_date?->toDateString(),
                    'expiry_date' => $userPackage->expiry_date?->toDateString(),
                    'is_custom_package' => $userPackage->is_custom_package,
                    'custom_price' => $userPackage->custom_price,
                    'custom_sessions' => $userPackage->custom_sessions,
                    'custom_duration_days' => $userPackage->custom_duration_days,
                    'assigned_by' => $userPackage->assigned_by,
                    'custom_assigned_at' => $userPackage->custom_assigned_at?->toISOString(),
                ];
            });

        return response()->json([
            'message' => 'User packages retrieved successfully',
            'data' => [
                'user_id' => $userId,
                'user_name' => $user->name,
                'total_packages' => $packages->count(),
                'packages' => $packages,
            ],
        ], 200);
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
     * Update payment information for a user package
     */
    public function updatePayment(Request $request, int $userId, int $userPackageId)
    {
        $validated = $request->validate([
            'amount_paid' => 'required|numeric|min:0',
            'amount_remaining' => 'required|numeric|min:0',
            'payment_status' => 'required|string|in:pending,partial,fully_paid',
            'payment_method' => 'nullable|string|in:cash,card,transfer,iris,cash_a',
            'payment_notes' => 'nullable|string|max:1000',
        ]);

        $user = User::find($userId);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $userPackage = UserPackage::where('id', $userPackageId)
            ->where('user_id', $userId)
            ->first();

        if (!$userPackage) {
            return response()->json(['message' => 'User package not found'], 404);
        }

        // Store old values for history
        $oldAmountPaid = $userPackage->amount_paid;
        $oldAmountRemaining = $userPackage->amount_remaining;
        $oldPaymentStatus = $userPackage->payment_status;
        $paymentDifference = $validated['amount_paid'] - $oldAmountPaid;

        // Update payment information
        $userPackage->amount_paid = $validated['amount_paid'];
        $userPackage->amount_remaining = $validated['amount_remaining'];
        $userPackage->payment_status = $validated['payment_status'];

        if (isset($validated['payment_method'])) {
            $userPackage->payment_method = $validated['payment_method'];
        }

        if (isset($validated['payment_notes'])) {
            $userPackage->payment_notes = $validated['payment_notes'];
        }

        if (isset($validated['installments_count'])) {
            $userPackage->installments = $validated['installments_count'];
        }

        if (isset($validated['installment_frequency'])) {
            $userPackage->installment_frequency = $validated['installment_frequency'];
        }

        // Auto-calculate installments paid based on amount paid
        if ($userPackage->installments > 1) {
            $totalAmount = $validated['amount_paid'] + $validated['amount_remaining'];
            $installmentAmount = $totalAmount / $userPackage->installments;

            // Calculate how many full installments have been paid
            if ($installmentAmount > 0) {
                $userPackage->installments_paid = floor($validated['amount_paid'] / $installmentAmount);
            }
        }

        $userPackage->save();

        // Log the payment update
        $userPackage->logHistory('payment_updated', [
            'old_amount_paid' => $oldAmountPaid,
            'new_amount_paid' => $validated['amount_paid'],
            'payment_difference' => $paymentDifference,
            'old_amount_remaining' => $oldAmountRemaining,
            'new_amount_remaining' => $validated['amount_remaining'],
            'old_payment_status' => $oldPaymentStatus,
            'new_payment_status' => $validated['payment_status'],
            'payment_method' => $validated['payment_method'] ?? null,
            'notes' => $validated['payment_notes'] ?? null,
            'updated_by' => auth()->user()->name ?? 'Admin',
            'updated_by_id' => auth()->id(),
        ]);

        // Record the additional payment in cash register if amount increased
        if ($paymentDifference > 0 && isset($validated['payment_method'])) {
            try {
                \App\Models\CashRegisterEntry::create([
                    'type' => 'income',
                    'amount' => $paymentDifference,
                    'description' => "Πληρωμή πακέτου (δόση): {$userPackage->name} - {$user->name}",
                    'payment_method' => $validated['payment_method'],
                    'user_id' => auth()->id() ?? 1,
                    'category' => 'Package Payment',
                    'related_entity_type' => 'UserPackage',
                    'related_entity_id' => $userPackage->id,
                    'store_id' => $request->input('store_id', 1),
                ]);
            } catch (\Exception $e) {
                \Log::warning('Failed to create cash register entry for payment update: ' . $e->getMessage());
            }
        }

        return response()->json([
            'message' => 'Payment updated successfully',
            'user_package' => $userPackage,
        ], 200);
    }
} 