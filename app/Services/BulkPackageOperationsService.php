<?php

namespace App\Services;

use App\Models\UserPackage;
use App\Models\User;
use App\Models\Package;
use App\Models\BulkOperation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

class BulkPackageOperationsService
{
    /**
     * Preview bulk package extension operation
     */
    public function previewExtension(array $filters, array $extensionData)
    {
        $query = $this->buildUserPackageQuery($filters);
        $packages = $query->get();
        
        $preview = [];
        foreach ($packages as $package) {
            $preview[] = [
                'user_package_id' => $package->id,
                'user_name' => $package->user->name,
                'user_email' => $package->user->email,
                'package_name' => $package->name,
                'current_expiry' => $package->expiry_date,
                'current_sessions' => $package->remaining_sessions,
                'new_expiry' => $this->calculateNewExpiryDate($package, $extensionData),
                'new_sessions' => $this->calculateNewSessions($package, $extensionData),
                'price_adjustment' => $this->calculatePriceAdjustment($package, $extensionData),
            ];
        }
        
        return [
            'affected_count' => count($preview),
            'packages' => $preview,
            'summary' => $this->generatePreviewSummary($preview),
        ];
    }
    
    /**
     * Execute bulk package extension operation
     */
    public function executeExtension(array $filters, array $extensionData, $adminId)
    {
        $query = $this->buildUserPackageQuery($filters);
        $packages = $query->get();
        
        if ($packages->isEmpty()) {
            throw new Exception('No packages found matching the specified criteria');
        }
        
        // Create bulk operation record
        $bulkOperation = BulkOperation::create([
            'type' => 'package_extension',
            'performed_by' => $adminId,
            'target_count' => $packages->count(),
            'status' => 'in_progress',
            'filters' => json_encode($filters),
            'operation_data' => json_encode($extensionData),
            'started_at' => now(),
        ]);
        
        $successful = 0;
        $failed = 0;
        $errors = [];
        
        DB::transaction(function () use ($packages, $extensionData, $bulkOperation, &$successful, &$failed, &$errors) {
            foreach ($packages as $package) {
                try {
                    $this->extendSinglePackage($package, $extensionData, $bulkOperation->id);
                    $successful++;
                } catch (Exception $e) {
                    $failed++;
                    $errors[] = [
                        'package_id' => $package->id,
                        'user_name' => $package->user->name,
                        'error' => $e->getMessage(),
                    ];
                    Log::error('Bulk extension failed for package ' . $package->id, [
                        'error' => $e->getMessage(),
                        'bulk_operation_id' => $bulkOperation->id,
                    ]);
                }
            }
        });
        
        // Update bulk operation status
        $bulkOperation->update([
            'status' => $failed > 0 ? 'completed_with_errors' : 'completed',
            'successful_count' => $successful,
            'failed_count' => $failed,
            'errors' => json_encode($errors),
            'completed_at' => now(),
        ]);
        
        return [
            'bulk_operation_id' => $bulkOperation->id,
            'successful' => $successful,
            'failed' => $failed,
            'errors' => $errors,
        ];
    }
    
    /**
     * Apply bulk pricing adjustments
     */
    public function applyPricingAdjustments(array $filters, array $pricingData, $adminId)
    {
        $query = $this->buildUserPackageQuery($filters);
        $packages = $query->get();
        
        if ($packages->isEmpty()) {
            throw new Exception('No packages found matching the specified criteria');
        }
        
        // Create bulk operation record
        $bulkOperation = BulkOperation::create([
            'type' => 'pricing_adjustment',
            'performed_by' => $adminId,
            'target_count' => $packages->count(),
            'status' => 'in_progress',
            'filters' => json_encode($filters),
            'operation_data' => json_encode($pricingData),
            'started_at' => now(),
        ]);
        
        $successful = 0;
        $failed = 0;
        $errors = [];
        
        DB::transaction(function () use ($packages, $pricingData, $bulkOperation, &$successful, &$failed, &$errors) {
            foreach ($packages as $package) {
                try {
                    $this->applyPricingToPackage($package, $pricingData, $bulkOperation->id);
                    $successful++;
                } catch (Exception $e) {
                    $failed++;
                    $errors[] = [
                        'package_id' => $package->id,
                        'user_name' => $package->user->name,
                        'error' => $e->getMessage(),
                    ];
                }
            }
        });
        
        // Update bulk operation status
        $bulkOperation->update([
            'status' => $failed > 0 ? 'completed_with_errors' : 'completed',
            'successful_count' => $successful,
            'failed_count' => $failed,
            'errors' => json_encode($errors),
            'completed_at' => now(),
        ]);
        
        return [
            'bulk_operation_id' => $bulkOperation->id,
            'successful' => $successful,
            'failed' => $failed,
            'errors' => $errors,
        ];
    }
    
    /**
     * Get bulk operation status
     */
    public function getOperationStatus($bulkOperationId)
    {
        $operation = BulkOperation::find($bulkOperationId);
        if (!$operation) {
            throw new Exception('Bulk operation not found');
        }
        
        return [
            'id' => $operation->id,
            'type' => $operation->type,
            'status' => $operation->status,
            'target_count' => $operation->target_count,
            'successful_count' => $operation->successful_count,
            'failed_count' => $operation->failed_count,
            'progress_percentage' => $this->calculateProgress($operation),
            'started_at' => $operation->started_at,
            'completed_at' => $operation->completed_at,
            'errors' => json_decode($operation->errors, true),
        ];
    }
    
    /**
     * Build user package query based on filters
     */
    private function buildUserPackageQuery(array $filters)
    {
        $query = UserPackage::with(['user', 'package']);
        
        // Status filter
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        // Package type filter
        if (!empty($filters['package_id'])) {
            $query->where('package_id', $filters['package_id']);
        }
        
        // Expiry date range filter
        if (!empty($filters['expiry_from'])) {
            $query->whereDate('expiry_date', '>=', $filters['expiry_from']);
        }
        if (!empty($filters['expiry_to'])) {
            $query->whereDate('expiry_date', '<=', $filters['expiry_to']);
        }
        
        // User selection filter
        if (!empty($filters['user_ids'])) {
            $query->whereIn('user_id', $filters['user_ids']);
        }
        
        // User search filter
        if (!empty($filters['user_search'])) {
            $query->whereHas('user', function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['user_search'] . '%')
                  ->orWhere('email', 'like', '%' . $filters['user_search'] . '%');
            });
        }
        
        // Sessions remaining filter
        if (!empty($filters['min_sessions'])) {
            $query->where('remaining_sessions', '>=', $filters['min_sessions']);
        }
        if (!empty($filters['max_sessions'])) {
            $query->where('remaining_sessions', '<=', $filters['max_sessions']);
        }
        
        // Auto-renew filter
        if (isset($filters['auto_renew'])) {
            $query->where('auto_renew', $filters['auto_renew']);
        }
        
        return $query;
    }
    
    /**
     * Calculate new expiry date based on extension data
     */
    private function calculateNewExpiryDate(UserPackage $package, array $extensionData)
    {
        $currentExpiry = $package->expiry_date;
        $newExpiry = $currentExpiry;
        
        if (!empty($extensionData['extend_days'])) {
            $newExpiry = $newExpiry->addDays($extensionData['extend_days']);
        }
        
        if (!empty($extensionData['extend_weeks'])) {
            $newExpiry = $newExpiry->addWeeks($extensionData['extend_weeks']);
        }
        
        if (!empty($extensionData['extend_months'])) {
            $newExpiry = $newExpiry->addMonths($extensionData['extend_months']);
        }
        
        if (!empty($extensionData['set_expiry_date'])) {
            $newExpiry = Carbon::parse($extensionData['set_expiry_date']);
        }
        
        return $newExpiry;
    }
    
    /**
     * Calculate new sessions based on extension data
     */
    private function calculateNewSessions(UserPackage $package, array $extensionData)
    {
        $currentSessions = $package->remaining_sessions;
        $newSessions = $currentSessions;
        
        if (!empty($extensionData['add_sessions'])) {
            $newSessions += $extensionData['add_sessions'];
        }
        
        if (!empty($extensionData['set_sessions'])) {
            $newSessions = $extensionData['set_sessions'];
        }
        
        return max(0, $newSessions);
    }
    
    /**
     * Calculate price adjustment
     */
    private function calculatePriceAdjustment(UserPackage $package, array $extensionData)
    {
        $adjustment = 0;
        
        if (!empty($extensionData['discount_amount'])) {
            $adjustment = -$extensionData['discount_amount'];
        }
        
        if (!empty($extensionData['discount_percentage'])) {
            $originalPrice = $package->package->price ?? 0;
            $adjustment = -($originalPrice * $extensionData['discount_percentage'] / 100);
        }
        
        return $adjustment;
    }
    
    /**
     * Extend a single package
     */
    private function extendSinglePackage(UserPackage $package, array $extensionData, $bulkOperationId)
    {
        $originalExpiry = $package->expiry_date;
        $originalSessions = $package->remaining_sessions;
        
        // Calculate new values
        $newExpiry = $this->calculateNewExpiryDate($package, $extensionData);
        $newSessions = $this->calculateNewSessions($package, $extensionData);
        
        // Update package
        $package->update([
            'expiry_date' => $newExpiry,
            'remaining_sessions' => $newSessions,
            'total_sessions' => max($package->total_sessions, $newSessions),
        ]);
        
        // Log the extension
        $package->logHistory('bulk_extended', [
            'bulk_operation_id' => $bulkOperationId,
            'original_expiry' => $originalExpiry,
            'new_expiry' => $newExpiry,
            'original_sessions' => $originalSessions,
            'new_sessions' => $newSessions,
            'extension_data' => $extensionData,
        ]);
    }
    
    /**
     * Apply pricing adjustments to a package
     */
    private function applyPricingToPackage(UserPackage $package, array $pricingData, $bulkOperationId)
    {
        $adjustment = $this->calculatePriceAdjustment($package, $pricingData);
        
        // Log the pricing adjustment
        $package->logHistory('bulk_price_adjusted', [
            'bulk_operation_id' => $bulkOperationId,
            'adjustment_amount' => $adjustment,
            'pricing_data' => $pricingData,
        ]);
    }
    
    /**
     * Generate preview summary
     */
    private function generatePreviewSummary(array $preview)
    {
        $totalExtensionDays = 0;
        $totalSessionsAdded = 0;
        $totalPriceAdjustment = 0;
        
        foreach ($preview as $item) {
            $totalExtensionDays += $item['current_expiry']->diffInDays($item['new_expiry']);
            $totalSessionsAdded += $item['new_sessions'] - $item['current_sessions'];
            $totalPriceAdjustment += $item['price_adjustment'];
        }
        
        return [
            'total_packages' => count($preview),
            'avg_extension_days' => count($preview) > 0 ? round($totalExtensionDays / count($preview), 1) : 0,
            'total_sessions_added' => $totalSessionsAdded,
            'total_price_adjustment' => $totalPriceAdjustment,
        ];
    }
    
    /**
     * Calculate operation progress
     */
    private function calculateProgress(BulkOperation $operation)
    {
        if ($operation->status === 'completed' || $operation->status === 'completed_with_errors') {
            return 100;
        }
        
        $processed = $operation->successful_count + $operation->failed_count;
        return $operation->target_count > 0 ? round(($processed / $operation->target_count) * 100, 2) : 0;
    }
}