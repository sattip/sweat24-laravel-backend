<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\BulkPackageExtensionRequest;
use App\Http\Requests\BulkPricingAdjustmentRequest;
use App\Services\BulkPackageOperationsService;
use App\Services\PackageNotificationService;
use App\Models\Package;
use App\Models\User;
use App\Models\UserPackage;
use App\Models\BulkOperation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class BulkPackageController extends Controller
{
    protected $bulkService;
    protected $notificationService;

    public function __construct(
        BulkPackageOperationsService $bulkService,
        PackageNotificationService $notificationService
    ) {
        $this->bulkService = $bulkService;
        $this->notificationService = $notificationService;
    }

    /**
     * Display bulk operations interface
     */
    public function index()
    {
        $packages = Package::where('status', 'active')->get();
        $users = User::where('status', 'active')->orderBy('name')->get();
        
        $stats = [
            'total_packages' => UserPackage::count(),
            'active_packages' => UserPackage::where('status', UserPackage::STATUS_ACTIVE)->count(),
            'expiring_soon' => UserPackage::where('status', UserPackage::STATUS_EXPIRING_SOON)->count(),
            'expired_packages' => UserPackage::where('status', UserPackage::STATUS_EXPIRED)->count(),
        ];

        return view('admin.packages.bulk', compact('packages', 'users', 'stats'));
    }

    /**
     * Preview bulk package extension
     */
    public function previewExtension(BulkPackageExtensionRequest $request)
    {
        try {
            $filters = $request->getValidatedFilters();
            $extensionData = $request->getValidatedExtension();
            
            $preview = $this->bulkService->previewExtension($filters, $extensionData);
            
            // Generate confirmation token for actual operation
            $confirmationToken = Str::random(32);
            Cache::put("bulk_extension_token_{$confirmationToken}", [
                'filters' => $filters,
                'extension_data' => $extensionData,
                'admin_id' => auth()->id(),
            ], now()->addMinutes(10));
            
            return response()->json([
                'success' => true,
                'preview' => $preview,
                'confirmation_token' => $confirmationToken,
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Execute bulk package extension
     */
    public function executeExtension(BulkPackageExtensionRequest $request)
    {
        if ($request->isPreviewOnly()) {
            return $this->previewExtension($request);
        }

        try {
            $token = $request->getConfirmationToken();
            $cachedData = Cache::get("bulk_extension_token_{$token}");
            
            if (!$cachedData) {
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid or expired confirmation token',
                ], 400);
            }

            $result = $this->bulkService->executeExtension(
                $cachedData['filters'],
                $cachedData['extension_data'],
                $cachedData['admin_id']
            );

            // Clear the confirmation token
            Cache::forget("bulk_extension_token_{$token}");

            // Send notifications if requested
            if ($request->shouldSendNotifications()) {
                $this->sendBulkNotifications($result['bulk_operation_id'], 'extension');
            }

            return response()->json([
                'success' => true,
                'result' => $result,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Preview bulk pricing adjustment
     */
    public function previewPricingAdjustment(BulkPricingAdjustmentRequest $request)
    {
        try {
            $filters = $request->getValidatedFilters();
            $pricingData = $request->getValidatedPricing();
            
            $preview = $this->bulkService->previewExtension($filters, $pricingData);
            
            // Generate confirmation token for actual operation
            $confirmationToken = Str::random(32);
            Cache::put("bulk_pricing_token_{$confirmationToken}", [
                'filters' => $filters,
                'pricing_data' => $pricingData,
                'admin_id' => auth()->id(),
            ], now()->addMinutes(10));
            
            return response()->json([
                'success' => true,
                'preview' => $preview,
                'confirmation_token' => $confirmationToken,
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Execute bulk pricing adjustment
     */
    public function executePricingAdjustment(BulkPricingAdjustmentRequest $request)
    {
        if ($request->isPreviewOnly()) {
            return $this->previewPricingAdjustment($request);
        }

        try {
            $token = $request->getConfirmationToken();
            $cachedData = Cache::get("bulk_pricing_token_{$token}");
            
            if (!$cachedData) {
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid or expired confirmation token',
                ], 400);
            }

            $result = $this->bulkService->applyPricingAdjustments(
                $cachedData['filters'],
                $cachedData['pricing_data'],
                $cachedData['admin_id']
            );

            // Clear the confirmation token
            Cache::forget("bulk_pricing_token_{$token}");

            // Send notifications if requested
            if ($request->shouldSendNotifications()) {
                $this->sendBulkNotifications($result['bulk_operation_id'], 'pricing');
            }

            return response()->json([
                'success' => true,
                'result' => $result,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get operation status
     */
    public function getOperationStatus($bulkOperationId)
    {
        try {
            $status = $this->bulkService->getOperationStatus($bulkOperationId);
            
            return response()->json([
                'success' => true,
                'status' => $status,
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get bulk operations history
     */
    public function history(Request $request)
    {
        $query = BulkOperation::with('performedBy')
            ->orderBy('created_at', 'desc');

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->has('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $operations = $query->paginate(20);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'operations' => $operations,
            ]);
        }

        return view('admin.packages.bulk-history', compact('operations'));
    }

    /**
     * Show bulk operation details
     */
    public function showOperation(BulkOperation $operation)
    {
        $operation->load('performedBy');
        
        return view('admin.packages.bulk-operation', compact('operation'));
    }

    /**
     * Cancel running bulk operation
     */
    public function cancelOperation(BulkOperation $operation)
    {
        if ($operation->status !== 'in_progress') {
            return response()->json([
                'success' => false,
                'error' => 'Operation is not in progress',
            ], 400);
        }

        $operation->update([
            'status' => 'cancelled',
            'completed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Operation cancelled successfully',
        ]);
    }

    /**
     * Get filtered packages for preview
     */
    public function getFilteredPackages(Request $request)
    {
        $filters = $request->validate([
            'status' => 'nullable|string',
            'package_id' => 'nullable|integer|exists:packages,id',
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'integer|exists:users,id',
            'user_search' => 'nullable|string',
            'expiry_from' => 'nullable|date',
            'expiry_to' => 'nullable|date',
            'min_sessions' => 'nullable|integer',
            'max_sessions' => 'nullable|integer',
            'auto_renew' => 'nullable|boolean',
        ]);

        try {
            $preview = $this->bulkService->previewExtension($filters, []);
            
            return response()->json([
                'success' => true,
                'count' => $preview['affected_count'],
                'packages' => collect($preview['packages'])->take(10), // Limit preview
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Send bulk notifications
     */
    private function sendBulkNotifications($bulkOperationId, $type)
    {
        $operation = BulkOperation::find($bulkOperationId);
        if (!$operation) {
            return;
        }

        // Get affected packages
        $packages = UserPackage::whereHas('history', function ($query) use ($bulkOperationId) {
            $query->where('notes', 'like', '%"bulk_operation_id":' . $bulkOperationId . '%');
        })->get();

        foreach ($packages as $package) {
            try {
                if ($type === 'extension') {
                    $this->notificationService->sendPackageExtensionNotification($package);
                } elseif ($type === 'pricing') {
                    $this->notificationService->sendPricingAdjustmentNotification($package);
                }
            } catch (\Exception $e) {
                // Log error but don't stop processing
                \Log::error("Failed to send bulk notification for package {$package->id}: " . $e->getMessage());
            }
        }
    }
}