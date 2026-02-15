<?php

namespace App\Services;

use App\Models\CashRegisterEntry;
use App\Models\Store;
use App\Models\UserPackage;
use App\Models\Booking;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CashRegisterService
{
    /**
     * Record income from completed booking/package usage
     */
    public function recordPackageIncome(Booking $booking, UserPackage $userPackage): CashRegisterEntry
    {
        // Calculate per-training cost
        $perTrainingCost = $this->calculatePerTrainingCost($userPackage);

        $cashEntry = CashRegisterEntry::create([
            'type' => 'income',
            'amount' => $perTrainingCost,
            'description' => "Package usage - {$userPackage->name}",
            'category' => 'package_usage',
            'user_id' => $booking->user_id,
            'store_id' => $booking->store_id,
            'related_entity_id' => $userPackage->id,
            'related_entity_type' => 'user_package',
            'payment_method' => 'package_credit',
        ]);

        Log::info('Package income recorded', [
            'booking_id' => $booking->id,
            'user_package_id' => $userPackage->id,
            'store_id' => $booking->store_id,
            'amount' => $perTrainingCost,
        ]);

        return $cashEntry;
    }

    /**
     * Record expense
     */
    public function recordExpense(array $data): CashRegisterEntry
    {
        $cashEntry = CashRegisterEntry::create([
            'type' => 'withdrawal',
            'amount' => $data['amount'],
            'description' => $data['description'] ?? '',
            'category' => $data['category'],
            'user_id' => auth()->id(),
            'store_id' => $data['store_id'],
            'payment_method' => $data['payment_method'] ?? 'cash',
        ]);

        Log::info('Expense recorded', [
            'store_id' => $data['store_id'],
            'amount' => $data['amount'],
            'category' => $data['category'],
        ]);

        return $cashEntry;
    }

    /**
     * Calculate per-training cost from package
     */
    public function calculatePerTrainingCost(UserPackage $userPackage): float
    {
        if ($userPackage->total_sessions <= 0) {
            return 0;
        }

        $price = $userPackage->custom_price ?? $userPackage->package->price;
        return round($price / $userPackage->total_sessions, 2);
    }

    /**
     * Get store financial summary
     */
    public function getStoreSummary(int $storeId, ?string $startDate = null, ?string $endDate = null): array
    {
        $query = CashRegisterEntry::where('store_id', $storeId);

        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        $entries = $query->get();

        $income = $entries->where('type', 'income')->sum('amount');
        $expenses = $entries->where('type', 'withdrawal')->sum('amount');
        $net = $income - $expenses;

        return [
            'store_id' => $storeId,
            'income' => $income,
            'expenses' => $expenses,
            'net' => $net,
            'entries_count' => $entries->count(),
        ];
    }

    /**
     * Get package consumption report
     */
    public function getPackageConsumptionReport(int $packageId): array
    {
        $userPackage = UserPackage::with(['package', 'user'])->findOrFail($packageId);

        $usedSessions = $userPackage->total_sessions - $userPackage->remaining_sessions;
        $perTrainingCost = $this->calculatePerTrainingCost($userPackage);
        $totalRevenue = $usedSessions * $perTrainingCost;

        return [
            'user_package_id' => $userPackage->id,
            'package_name' => $userPackage->name,
            'user_name' => $userPackage->user->name,
            'total_sessions' => $userPackage->total_sessions,
            'used_sessions' => $usedSessions,
            'remaining_sessions' => $userPackage->remaining_sessions,
            'per_training_cost' => $perTrainingCost,
            'total_revenue' => $totalRevenue,
        ];
    }

    /**
     * Handle rounding for last session to ensure totals match
     */
    public function handleRoundingAdjustment(UserPackage $userPackage, float $standardAmount): float
    {
        $usedSessions = $userPackage->total_sessions - $userPackage->remaining_sessions;
        $remainingAfterThis = $userPackage->remaining_sessions - 1;

        // If this is the last session, adjust amount to match package total
        if ($remainingAfterThis === 0) {
            $totalPackagePrice = $userPackage->package->price;
            $previousCharges = ($usedSessions) * $standardAmount;
            $adjustment = $totalPackagePrice - $previousCharges;

            return round($adjustment, 2);
        }

        return $standardAmount;
    }
}
