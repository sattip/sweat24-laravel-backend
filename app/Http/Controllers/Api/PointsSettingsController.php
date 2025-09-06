<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PointsSetting;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class PointsSettingsController extends Controller
{
    /**
     * Get points settings
     */
    public function getSettings(): JsonResponse
    {
        // Get all current settings with defaults
        $defaultSettings = [
            'pointsPerEuro' => 1,
            'isActive' => true,
            'bonusMultiplier' => 1,
            'minimumPurchase' => 0,
            'maximumPointsPerTransaction' => 1000,
        ];

        // Get stored settings
        $storedSettings = [];
        
        $storedSettings['pointsPerEuro'] = PointsSetting::get('points_per_euro', $defaultSettings['pointsPerEuro']);
        $storedSettings['isActive'] = PointsSetting::get('points_system_active', $defaultSettings['isActive']);
        $storedSettings['bonusMultiplier'] = PointsSetting::get('bonus_multiplier', $defaultSettings['bonusMultiplier']);
        $storedSettings['minimumPurchase'] = PointsSetting::get('minimum_purchase', $defaultSettings['minimumPurchase']);
        $storedSettings['maximumPointsPerTransaction'] = PointsSetting::get('max_points_per_transaction', $defaultSettings['maximumPointsPerTransaction']);

        return response()->json([
            'success' => true,
            'data' => $storedSettings
        ]);
    }

    /**
     * Update points settings
     */
    public function updateSettings(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'pointsPerEuro' => 'required|numeric|min:0|max:100',
                'isActive' => 'required|boolean',
                'bonusMultiplier' => 'required|numeric|min:0.1|max:10',
                'minimumPurchase' => 'required|numeric|min:0|max:1000',
                'maximumPointsPerTransaction' => 'required|integer|min:1|max:10000',
            ]);

            // Save each setting individually
            PointsSetting::set(
                'points_per_euro', 
                $validatedData['pointsPerEuro'], 
                'float',
                'Πόντοι που κερδίζει ο χρήστης ανά ευρώ αγοράς'
            );

            PointsSetting::set(
                'points_system_active', 
                $validatedData['isActive'], 
                'boolean',
                'Κατάσταση ενεργοποίησης του συστήματος πόντων'
            );

            PointsSetting::set(
                'bonus_multiplier', 
                $validatedData['bonusMultiplier'], 
                'float',
                'Πολλαπλασιαστής bonus για ειδικές προσφορές'
            );

            PointsSetting::set(
                'minimum_purchase', 
                $validatedData['minimumPurchase'], 
                'float',
                'Ελάχιστο ποσό αγοράς για να κερδίσει πόντους'
            );

            PointsSetting::set(
                'max_points_per_transaction', 
                $validatedData['maximumPointsPerTransaction'], 
                'integer',
                'Μέγιστοι πόντοι που μπορεί να κερδίσει σε μία συναλλαγή'
            );

            return response()->json([
                'success' => true,
                'message' => 'Οι ρυθμίσεις πόντων ενημερώθηκαν επιτυχώς',
                'data' => $validatedData
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Σφάλμα επικύρωσης δεδομένων',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Παρουσιάστηκε σφάλμα κατά την αποθήκευση των ρυθμίσεων',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user points balance
     */
    public function getUserPoints(Request $request): JsonResponse
    {
        try {
            $userId = $request->input('user_id');
            
            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'User ID is required'
                ], 400);
            }

            $pointsService = app(\App\Services\PointsService::class);
            $balance = $pointsService->getUserBalance($userId);

            return response()->json([
                'success' => true,
                'data' => [
                    'user_id' => $userId,
                    'points_balance' => $balance
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get user points',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get points for multiple users
     */
    public function getUsersPoints(Request $request): JsonResponse
    {
        try {
            $userIds = $request->input('user_ids', []);
            
            if (empty($userIds) || !is_array($userIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'User IDs array is required'
                ], 400);
            }

            $pointsService = app(\App\Services\PointsService::class);
            $userPoints = [];

            foreach ($userIds as $userId) {
                $balance = $pointsService->getUserPointsBalance($userId);
                $userPoints[$userId] = $balance;
            }

            return response()->json([
                'success' => true,
                'data' => $userPoints
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get users points',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reset settings to defaults
     */
    public function resetSettings(): JsonResponse
    {
        try {
            $defaultSettings = [
                'points_per_euro' => [1, 'float', 'Πόντοι που κερδίζει ο χρήστης ανά ευρώ αγοράς'],
                'points_system_active' => [true, 'boolean', 'Κατάσταση ενεργοποίησης του συστήματος πόντων'],
                'bonus_multiplier' => [1, 'float', 'Πολλαπλασιαστής bonus για ειδικές προσφορές'],
                'minimum_purchase' => [0, 'float', 'Ελάχιστο ποσό αγοράς για να κερδίσει πόντους'],
                'max_points_per_transaction' => [1000, 'integer', 'Μέγιστοι πόντοι που μπορεί να κερδίσει σε μία συναλλαγή'],
            ];

            foreach ($defaultSettings as $key => [$value, $type, $description]) {
                PointsSetting::set($key, $value, $type, $description);
            }

            return response()->json([
                'success' => true,
                'message' => 'Οι ρυθμίσεις επαναφέρθηκαν στις προεπιλεγμένες τιμές',
                'data' => [
                    'pointsPerEuro' => 1,
                    'isActive' => true,
                    'bonusMultiplier' => 1,
                    'minimumPurchase' => 0,
                    'maximumPointsPerTransaction' => 1000,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Παρουσιάστηκε σφάλμα κατά την επαναφορά των ρυθμίσεων',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
