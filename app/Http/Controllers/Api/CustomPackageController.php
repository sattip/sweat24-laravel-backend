<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserPackage;
use App\Models\Package;
use App\Models\User;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CustomPackageController extends Controller
{
    use \App\Traits\ApiResponseTrait;

    /**
     * Display a listing of custom packages for a user
     */
    public function index(Request $request, $userId)
    {
        try {

            $customPackages = UserPackage::where('user_id', $userId)
                ->where('is_custom_package', true)
                ->with(['package.service', 'user'])
                ->orderBy('custom_assigned_at', 'desc')
                ->get()
                ->map(function ($userPackage) {
                    return [
                        'id' => $userPackage->id,
                        'package_name' => $userPackage->package->name,
                        'service_name' => $userPackage->package->service->name,
                        'custom_details' => $userPackage->getCustomPackageSummary(),
                        'status' => $userPackage->status,
                        'remaining_sessions' => $userPackage->remaining_sessions,
                        'expiry_date' => $userPackage->expiry_date,
                        'assigned_at' => $userPackage->custom_assigned_at,
                        'assigned_by' => $userPackage->assigned_by,
                    ];
                });

            return $this->successResponse($customPackages, 'Custom packages retrieved successfully');

        } catch (\Exception $e) {
            \Log::error('Error retrieving custom packages', [
                'user_id' => $request->get('user_id'),
                'error' => $e->getMessage()
            ]);

            return $this->errorResponse('Failed to retrieve custom packages', 500);
        }
    }

    /**
     * Store a newly created custom package
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer|exists:users,id',
            'package_id' => 'required|integer|exists:packages,id',
            'custom_price' => 'required|numeric|min:0',
            'custom_sessions' => 'required|integer|min:1',
            'custom_duration_days' => 'required|integer|min:1',
            'custom_notes' => 'nullable|string|max:1000',
            'assigned_by' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors(), 'Validation failed');
        }

        DB::beginTransaction();

        try {
            $user = User::findOrFail($request->user_id);
            $package = Package::findOrFail($request->package_id);

            // Check if user already has this exact custom package (to prevent true duplicates)
            $existingCustomPackage = UserPackage::where('user_id', $request->user_id)
                ->where('package_id', $request->package_id)
                ->where('is_custom_package', true)
                ->where('status', 'active')
                ->first();

            if ($existingCustomPackage) {
                return $this->businessValidationErrorResponse(
                    'Ο χρήστης έχει ήδη αυτό το custom πακέτο ενεργό.',
                    'DUPLICATE_CUSTOM_PACKAGE'
                );
            }

            // Create custom package
            $customPackage = UserPackage::create([
                'user_id' => $request->user_id,
                'package_id' => $request->package_id,
                'name' => "Custom: {$package->name} για {$user->name}",
                'assigned_date' => now(),
                'expiry_date' => now()->addDays($request->custom_duration_days),
                'remaining_sessions' => $request->custom_sessions,
                'total_sessions' => $request->custom_sessions,
                'status' => 'active',
                'is_custom_package' => true,
                'custom_price' => $request->custom_price,
                'custom_sessions' => $request->custom_sessions,
                'custom_duration_days' => $request->custom_duration_days,
                'custom_notes' => $request->custom_notes,
                'assigned_by' => $request->assigned_by ?: 'Admin',
                'custom_assigned_at' => now(),
            ]);

            DB::commit();

            return $this->successResponse([
                'custom_package' => $customPackage->load(['package.service', 'user']),
                'summary' => $customPackage->getCustomPackageSummary(),
                'savings' => $customPackage->getSavings(),
            ], 'Custom package assigned successfully', 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Failed to create custom package', 500);
        }
    }

    /**
     * Display the specified custom package
     */
    public function show(UserPackage $userPackage)
    {
        if (!$userPackage->is_custom_package) {
            return $this->errorResponse('This is not a custom package', 404);
        }

        return $this->successResponse([
            'custom_package' => $userPackage->load(['package.service', 'user']),
            'custom_details' => $userPackage->getCustomPackageSummary(),
            'effective_values' => [
                'price' => $userPackage->getEffectivePrice(),
                'sessions' => $userPackage->getEffectiveSessions(),
                'duration_days' => $userPackage->getEffectiveDurationDays(),
            ],
            'savings' => $userPackage->getSavings(),
        ], 'Custom package details retrieved successfully');
    }

    /**
     * Update the specified custom package
     */
    public function update(Request $request, UserPackage $userPackage)
    {
        if (!$userPackage->is_custom_package) {
            return $this->errorResponse('This is not a custom package', 400);
        }

        $validator = Validator::make($request->all(), [
            'custom_price' => 'sometimes|numeric|min:0',
            'custom_sessions' => 'sometimes|integer|min:1',
            'custom_duration_days' => 'sometimes|integer|min:1',
            'custom_notes' => 'nullable|string|max:1000',
            'status' => ['sometimes', Rule::in(['active', 'inactive', 'expired', 'cancelled'])],
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors(), 'Validation failed');
        }

        DB::beginTransaction();

        try {
            $updateData = [];

            if ($request->has('custom_price')) {
                $updateData['custom_price'] = $request->custom_price;
            }

            if ($request->has('custom_sessions')) {
                $updateData['custom_sessions'] = $request->custom_sessions;
                $updateData['total_sessions'] = $request->custom_sessions;
            }

            if ($request->has('custom_duration_days')) {
                $updateData['custom_duration_days'] = $request->custom_duration_days;
                $updateData['expiry_date'] = $userPackage->assigned_date->addDays($request->custom_duration_days);
            }

            if ($request->has('custom_notes')) {
                $updateData['custom_notes'] = $request->custom_notes;
            }

            if ($request->has('status')) {
                $updateData['status'] = $request->status;
            }

            $userPackage->update($updateData);
            DB::commit();

            return $this->successResponse([
                'custom_package' => $userPackage->fresh(['package.service', 'user']),
                'custom_details' => $userPackage->getCustomPackageSummary(),
                'savings' => $userPackage->getSavings(),
            ], 'Custom package updated successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Failed to update custom package', 500);
        }
    }

    /**
     * Remove the specified custom package
     */
    public function destroy(UserPackage $userPackage)
    {
        if (!$userPackage->is_custom_package) {
            return $this->errorResponse('This is not a custom package', 400);
        }

        $userPackage->delete();
        return $this->successResponse(null, 'Custom package deleted successfully');
    }

    /**
     * Get available packages for custom assignment
     */
    public function getAvailablePackages()
    {
        try {
            $packages = Package::where('status', 'active')
                ->with('service')
                ->orderBy('name')
                ->get()
                ->map(function ($package) {
                    return [
                        'id' => $package->id,
                        'name' => $package->name,
                        'service' => $package->service->name,
                        'original_price' => $package->price,
                        'original_sessions' => $package->sessions,
                        'original_duration' => $package->duration,
                        'description' => $package->description,
                    ];
                });

            return $this->successResponse($packages, 'Available packages retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve available packages', 500);
        }
    }

    /**
     * Get users eligible for custom packages
     */
    public function getEligibleUsers()
    {
        try {
            $users = User::select('id', 'name', 'email')
                ->where('status', 'active')
                ->orderBy('name')
                ->get()
                ->map(function ($user) {
                    $activePackagesCount = $user->userPackages()
                        ->where('status', 'active')
                        ->count();

                    $customPackagesCount = $user->userPackages()
                        ->where('is_custom_package', true)
                        ->count();

                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'active_packages_count' => $activePackagesCount,
                        'custom_packages_count' => $customPackagesCount,
                        'has_custom_treatment' => $customPackagesCount > 0,
                    ];
                });

            return $this->successResponse($users, 'Eligible users retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve eligible users', 500);
        }
    }

    /**
     * Check if user has special pricing for a service
     */
    public function checkSpecialPricing(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer|exists:users,id',
            'service_id' => 'required|integer|exists:services,id',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors(), 'Validation failed');
        }

        try {
            $userId = $request->get('user_id');
            $serviceId = $request->get('service_id');

            $hasSpecialPricing = UserPackage::hasSpecialPricingForService($userId, $serviceId);

            return $this->successResponse([
                'has_special_pricing' => $hasSpecialPricing,
                'service_id' => $serviceId,
                'user_id' => $userId,
            ], 'Special pricing check completed');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to check special pricing', 500);
        }
    }

    /**
     * Get detailed special pricing information for a user and service
     */
    public function getSpecialPricingDetails(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer|exists:users,id',
            'service_id' => 'required|integer|exists:services,id',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors(), 'Validation failed');
        }

        try {
            $userId = $request->get('user_id');
            $serviceId = $request->get('service_id');

            $specialPricingDetails = UserPackage::getSpecialPricingForService($userId, $serviceId);

            if ($specialPricingDetails) {
                return $this->successResponse($specialPricingDetails, 'Special pricing details retrieved');
            } else {
                return $this->successResponse([
                    'has_special_pricing' => false,
                    'service_id' => $serviceId,
                    'user_id' => $userId,
                    'message' => 'No special pricing found for this service'
                ], 'No special pricing found');
            }

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve special pricing details', 500);
        }
    }

    /**
     * Get complete special pricing summary for a user
     */
    public function getUserSpecialPricingSummary(Request $request, $userId)
    {
        try {

            $summary = UserPackage::getUserSpecialPricingSummary($userId);

            return $this->successResponse($summary, 'User special pricing summary retrieved');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve user special pricing summary', 500);
        }
    }

    /**
     * Get special pricing comparison for all user's packages
     */
    public function getSpecialPricingComparison(Request $request, $userId)
    {
        try {
            // Get all user's packages (both regular and custom)
            $allPackages = UserPackage::where('user_id', $userId)
                ->with('package.service')
                ->get();

            $comparison = [
                'user_id' => (int) $userId,
                'total_packages' => $allPackages->count(),
                'custom_packages_count' => $allPackages->where('is_custom_package', true)->count(),
                'regular_packages_count' => $allPackages->where('is_custom_package', false)->count(),
                'packages' => []
            ];

            foreach ($allPackages as $userPackage) {
                $packageData = [
                    'package_id' => $userPackage->id,
                    'package_name' => $userPackage->package->name,
                    'service_name' => $userPackage->package->service->name,
                    'is_custom_package' => $userPackage->is_custom_package,
                    'status' => $userPackage->status,
                    'remaining_sessions' => $userPackage->remaining_sessions,
                    'expiry_date' => $userPackage->expiry_date,
                ];

                if ($userPackage->is_custom_package) {
                    $packageData['pricing'] = [
                        'type' => 'custom',
                        'original_price' => $userPackage->package->price,
                        'custom_price' => $userPackage->custom_price,
                        'savings' => $userPackage->package->price - $userPackage->custom_price,
                        'original_sessions' => $userPackage->package->sessions,
                        'custom_sessions' => $userPackage->custom_sessions,
                        'original_duration' => $userPackage->package->duration,
                        'custom_duration' => $userPackage->custom_duration_days,
                    ];
                } else {
                    $packageData['pricing'] = [
                        'type' => 'standard',
                        'price' => $userPackage->package->price,
                        'sessions' => $userPackage->package->sessions,
                        'duration' => $userPackage->package->duration,
                    ];
                }

                $comparison['packages'][] = $packageData;
            }

            return $this->successResponse($comparison, 'Special pricing comparison retrieved');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve special pricing comparison', 500);
        }
    }
}
