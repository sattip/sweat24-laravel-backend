<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\User;
use App\Services\BookingCompletionService;
use App\Services\CashRegisterService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MultiStoreController extends Controller
{
    protected $bookingCompletionService;
    protected $cashRegisterService;

    public function __construct(
        BookingCompletionService $bookingCompletionService,
        CashRegisterService $cashRegisterService
    ) {
        $this->bookingCompletionService = $bookingCompletionService;
        $this->cashRegisterService = $cashRegisterService;
    }

    /**
     * Complete a booking (appointment) and handle package usage
     */
    public function completeBooking(Request $request, int $bookingId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'completed_by' => 'required|exists:users,id'
            ]);

            $result = $this->bookingCompletionService->completeBooking($bookingId, $validated['completed_by']);

            return response()->json([
                'success' => true,
                'message' => 'Booking completed successfully',
                'data' => $result
            ]);

        } catch (\App\Exceptions\BusinessValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error_code' => $e->getErrorCode(),
                'error_type' => 'business_validation',
                'timestamp' => now()->toISOString()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get user packages with usage information
     */
    public function getUserPackages(int $userId): JsonResponse
    {
        $user = User::findOrFail($userId);

        $packages = $this->bookingCompletionService->getUserPackageSummary($userId);

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $user->only(['id', 'name', 'email']),
                'packages' => $packages
            ]
        ]);
    }

    /**
     * Get package consumption report
     */
    public function getPackageReport(int $packageId): JsonResponse
    {
        $report = $this->cashRegisterService->getPackageConsumptionReport($packageId);

        return response()->json([
            'success' => true,
            'data' => $report
        ]);
    }
}
