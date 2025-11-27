<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BodyMeasurement;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class BodyMeasurementController extends Controller
{
    /**
     * Get all measurements for authenticated user
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        $measurements = BodyMeasurement::where('user_id', $user->id)
            ->orderBy('date', 'desc')
            ->get()
            ->map(function ($measurement) {
                return $measurement->toApiArray();
            });

        return response()->json($measurements);
    }

    /**
     * Store a new measurement
     */
    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date|before_or_equal:today',
            'weight' => 'nullable|numeric|min:30|max:300',
            'height' => 'nullable|numeric|min:100|max:250',
            'waist' => 'nullable|numeric|min:50|max:200',
            'hips' => 'nullable|numeric|min:60|max:200',
            'chest' => 'nullable|numeric|min:60|max:200',
            'arm' => 'nullable|numeric|min:20|max:60',
            'thigh' => 'nullable|numeric|min:30|max:100',
            'body_fat' => 'nullable|numeric|min:3|max:50',
            'notes' => 'nullable|string|max:500',
        ]);

        $user = $request->user();

        try {
            $measurement = BodyMeasurement::create([
                'user_id' => $user->id,
                'date' => $request->date,
                'weight' => $request->weight,
                'height' => $request->height,
                'waist' => $request->waist,
                'hips' => $request->hips,
                'chest' => $request->chest,
                'arm' => $request->arm,
                'thigh' => $request->thigh,
                'body_fat' => $request->body_fat,
                'notes' => $request->notes,
            ]);

            // Log activity
            ActivityLogger::log(
                'body_measurement',
                'added measurement',
                $user,
                ['date' => $request->date]
            );

            return response()->json([
                'message' => 'Η μέτρηση αποθηκεύτηκε επιτυχώς',
                'measurement' => $measurement->toApiArray(),
            ], 201);

        } catch (\Illuminate\Database\QueryException $e) {
            // Handle unique constraint violation (one measurement per date)
            if ($e->getCode() === '23000') {
                return response()->json([
                    'message' => 'Υπάρχει ήδη μέτρηση για αυτήν την ημερομηνία',
                    'errors' => ['date' => ['Επιλέξτε διαφορετική ημερομηνία']],
                ], 422);
            }

            Log::error('Measurement creation failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Αποτυχία αποθήκευσης μέτρησης',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get specific measurement
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();
        
        $measurement = BodyMeasurement::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$measurement) {
            return response()->json([
                'message' => 'Η μέτρηση δεν βρέθηκε',
            ], 404);
        }

        return response()->json($measurement->toApiArray());
    }

    /**
     * Update measurement
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'date' => 'required|date|before_or_equal:today',
            'weight' => 'nullable|numeric|min:30|max:300',
            'height' => 'nullable|numeric|min:100|max:250',
            'waist' => 'nullable|numeric|min:50|max:200',
            'hips' => 'nullable|numeric|min:60|max:200',
            'chest' => 'nullable|numeric|min:60|max:200',
            'arm' => 'nullable|numeric|min:20|max:60',
            'thigh' => 'nullable|numeric|min:30|max:100',
            'body_fat' => 'nullable|numeric|min:3|max:50',
            'notes' => 'nullable|string|max:500',
        ]);

        $user = $request->user();
        
        $measurement = BodyMeasurement::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$measurement) {
            return response()->json([
                'message' => 'Η μέτρηση δεν βρέθηκε',
            ], 404);
        }

        try {
            $measurement->update([
                'date' => $request->date,
                'weight' => $request->weight,
                'height' => $request->height,
                'waist' => $request->waist,
                'hips' => $request->hips,
                'chest' => $request->chest,
                'arm' => $request->arm,
                'thigh' => $request->thigh,
                'body_fat' => $request->body_fat,
                'notes' => $request->notes,
            ]);

            // Log activity
            ActivityLogger::log(
                'body_measurement',
                'updated measurement',
                $user,
                ['measurement_id' => $id, 'date' => $request->date]
            );

            return response()->json([
                'message' => 'Η μέτρηση ενημερώθηκε επιτυχώς',
                'measurement' => $measurement->fresh()->toApiArray(),
            ]);

        } catch (\Exception $e) {
            Log::error('Measurement update failed', [
                'user_id' => $user->id,
                'measurement_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Αποτυχία ενημέρωσης μέτρησης',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete measurement
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        
        $measurement = BodyMeasurement::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$measurement) {
            return response()->json([
                'message' => 'Η μέτρηση δεν βρέθηκε',
            ], 404);
        }

        try {
            $date = $measurement->date;
            $measurement->delete();

            // Log activity
            ActivityLogger::log(
                'body_measurement',
                'deleted measurement',
                $user,
                ['date' => $date]
            );

            return response()->json([
                'message' => 'Η μέτρηση διαγράφηκε επιτυχώς',
            ]);

        } catch (\Exception $e) {
            Log::error('Measurement deletion failed', [
                'user_id' => $user->id,
                'measurement_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Αποτυχία διαγραφής μέτρησης',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get latest measurement
     */
    public function latest(Request $request)
    {
        $user = $request->user();
        
        $measurement = BodyMeasurement::where('user_id', $user->id)
            ->orderBy('date', 'desc')
            ->first();

        if (!$measurement) {
            return response()->json([
                'message' => 'Δεν βρέθηκε μέτρηση',
            ], 404);
        }

        return response()->json($measurement->toApiArray());
    }

    /**
     * Get comparison with previous measurement
     */
    public function comparison(Request $request)
    {
        $user = $request->user();
        
        $measurements = BodyMeasurement::where('user_id', $user->id)
            ->orderBy('date', 'desc')
            ->limit(2)
            ->get();

        if ($measurements->count() < 2) {
            return response()->json([
                'message' => 'Χρειάζονται τουλάχιστον 2 μετρήσεις για σύγκριση',
            ], 404);
        }

        $latest = $measurements->first();
        $previous = $measurements->last();

        return response()->json([
            'latest' => $latest->toApiArray(),
            'previous' => $previous->toApiArray(),
        ]);
    }

    /**
     * Admin/Trainer: Get measurements for specific user
     */
    public function getUserMeasurements(Request $request, $userId)
    {
        // Check if requester is admin or trainer
        $user = $request->user();
        if (!in_array($user->role, ['admin', 'trainer']) || $user->status !== 'active') {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        $measurements = BodyMeasurement::where('user_id', $userId)
            ->orderBy('date', 'desc')
            ->get()
            ->map(function ($measurement) {
                return $measurement->toApiArray();
            });

        return response()->json($measurements);
    }

    /**
     * Admin/Trainer: Get latest measurement for specific user
     */
    public function getUserLatestMeasurement(Request $request, $userId)
    {
        // Check if requester is admin or trainer
        $user = $request->user();
        if (!in_array($user->role, ['admin', 'trainer']) || $user->status !== 'active') {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        $measurement = BodyMeasurement::where('user_id', $userId)
            ->orderBy('date', 'desc')
            ->first();

        if (!$measurement) {
            return response()->json([
                'message' => 'Δεν βρέθηκε μέτρηση',
            ], 404);
        }

        return response()->json($measurement->toApiArray());
    }
}