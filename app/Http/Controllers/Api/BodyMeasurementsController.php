<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BodyMeasurement;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BodyMeasurementsController extends Controller
{
    /**
     * Get all body measurements for a user.
     */
    public function index(Request $request, $userId = null): JsonResponse
    {
        $query = BodyMeasurement::with(['user', 'measurer']);

        if ($userId) {
            $query->forUser($userId);
        }

        // Filter by date range
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->betweenDates($request->start_date, $request->end_date);
        }

        // Filter by measurer
        if ($request->has('measured_by')) {
            $query->where('measured_by', $request->measured_by);
        }

        $measurements = $query->latest()->get();

        // Add changes to each measurement
        $measurementsWithChanges = $measurements->map(function ($measurement) {
            $data = $measurement->toArray();
            $data['changes'] = $measurement->calculateChanges();
            return $data;
        });

        return response()->json([
            'success' => true,
            'data' => $measurementsWithChanges
        ]);
    }

    /**
     * Store a new body measurement.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'measurement_date' => 'required|date',
            'weight' => 'nullable|numeric|min:0|max:500',
            'body_fat_percentage' => 'nullable|numeric|min:0|max:100',
            'muscle_mass' => 'nullable|numeric|min:0|max:500',
            'chest' => 'nullable|numeric|min:0|max:300',
            'waist' => 'nullable|numeric|min:0|max:300',
            'hips' => 'nullable|numeric|min:0|max:300',
            'thighs' => 'nullable|numeric|min:0|max:300',
            'arms' => 'nullable|numeric|min:0|max:300',
            'calves' => 'nullable|numeric|min:0|max:300',
            'notes' => 'nullable|string|max:2000',
            'measured_by' => 'nullable|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $measurement = BodyMeasurement::create([
                'user_id' => $request->user_id,
                'measurement_date' => $request->measurement_date,
                'weight' => $request->weight,
                'body_fat_percentage' => $request->body_fat_percentage,
                'muscle_mass' => $request->muscle_mass,
                'chest' => $request->chest,
                'waist' => $request->waist,
                'hips' => $request->hips,
                'thighs' => $request->thighs,
                'arms' => $request->arms,
                'calves' => $request->calves,
                'notes' => $request->notes,
                'measured_by' => $request->measured_by ?? auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Body measurement created successfully',
                'data' => $measurement->load(['user', 'measurer'])
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create body measurement',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified body measurement.
     */
    public function show($id): JsonResponse
    {
        $measurement = BodyMeasurement::with(['user', 'measurer'])->findOrFail($id);

        $data = $measurement->toArray();
        $data['changes'] = $measurement->calculateChanges();

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * Update the specified body measurement.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $measurement = BodyMeasurement::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'measurement_date' => 'sometimes|required|date',
            'weight' => 'nullable|numeric|min:0|max:500',
            'body_fat_percentage' => 'nullable|numeric|min:0|max:100',
            'muscle_mass' => 'nullable|numeric|min:0|max:500',
            'chest' => 'nullable|numeric|min:0|max:300',
            'waist' => 'nullable|numeric|min:0|max:300',
            'hips' => 'nullable|numeric|min:0|max:300',
            'thighs' => 'nullable|numeric|min:0|max:300',
            'arms' => 'nullable|numeric|min:0|max:300',
            'calves' => 'nullable|numeric|min:0|max:300',
            'notes' => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $measurement->update($request->only([
            'measurement_date', 'weight', 'body_fat_percentage', 'muscle_mass',
            'chest', 'waist', 'hips', 'thighs', 'arms', 'calves', 'notes'
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Body measurement updated successfully',
            'data' => $measurement->load(['user', 'measurer'])
        ]);
    }

    /**
     * Remove the specified body measurement.
     */
    public function destroy($id): JsonResponse
    {
        $measurement = BodyMeasurement::findOrFail($id);
        $measurement->delete();

        return response()->json([
            'success' => true,
            'message' => 'Body measurement deleted successfully'
        ]);
    }

    /**
     * Get the latest body measurement for a user.
     */
    public function latest($userId): JsonResponse
    {
        $measurement = BodyMeasurement::getLatestForUser($userId);

        if (!$measurement) {
            return response()->json([
                'success' => true,
                'data' => null,
                'message' => 'No measurements found for this user'
            ]);
        }

        $data = $measurement->toArray();
        $data['changes'] = $measurement->calculateChanges();

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * Get measurement trends over time for a user.
     */
    public function trends(Request $request, $userId): JsonResponse
    {
        $user = User::findOrFail($userId);

        $startDate = $request->get('start_date', now()->subMonths(6)->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));

        $measurements = BodyMeasurement::forUser($userId)
            ->betweenDates($startDate, $endDate)
            ->orderBy('measurement_date', 'asc')
            ->get();

        if ($measurements->isEmpty()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'period' => [
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                    ],
                    'measurements' => [],
                    'trends' => [],
                    'latest' => null,
                ]
            ]);
        }

        // Format measurements for chart
        $chartData = $measurements->map(function ($m) {
            return [
                'date' => $m->measurement_date->format('Y-m-d'),
                'weight' => $m->weight,
                'body_fat_percentage' => $m->body_fat_percentage,
                'muscle_mass' => $m->muscle_mass,
                'chest' => $m->chest,
                'waist' => $m->waist,
                'hips' => $m->hips,
                'thighs' => $m->thighs,
                'arms' => $m->arms,
                'calves' => $m->calves,
            ];
        });

        // Calculate overall trends
        $first = $measurements->first();
        $latest = $measurements->last();

        $trends = [];
        $fields = ['weight', 'body_fat_percentage', 'muscle_mass', 'chest', 'waist', 'hips', 'thighs', 'arms', 'calves'];

        foreach ($fields as $field) {
            if ($first->$field && $latest->$field) {
                $change = $latest->$field - $first->$field;
                $changePercentage = ($change / $first->$field) * 100;

                $trends[$field] = [
                    'first' => $first->$field,
                    'latest' => $latest->$field,
                    'change' => round($change, 1),
                    'change_percentage' => round($changePercentage, 1),
                    'direction' => $change > 0 ? 'up' : ($change < 0 ? 'down' : 'stable'),
                ];
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'period' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                ],
                'measurements' => $chartData,
                'trends' => $trends,
                'latest' => $latest->load(['measurer']),
                'total_measurements' => $measurements->count(),
            ]
        ]);
    }

    /**
     * Compare two body measurements.
     */
    public function compare(Request $request, $userId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'measurement_id_1' => 'required|exists:body_measurements,id',
            'measurement_id_2' => 'required|exists:body_measurements,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $measurement1 = BodyMeasurement::findOrFail($request->measurement_id_1);
        $measurement2 = BodyMeasurement::findOrFail($request->measurement_id_2);

        // Ensure both measurements belong to the specified user
        if ($measurement1->user_id != $userId || $measurement2->user_id != $userId) {
            return response()->json([
                'success' => false,
                'message' => 'Measurements must belong to the specified user'
            ], 403);
        }

        // Calculate differences
        $comparison = [];
        $fields = ['weight', 'body_fat_percentage', 'muscle_mass', 'chest', 'waist', 'hips', 'thighs', 'arms', 'calves'];

        foreach ($fields as $field) {
            if ($measurement1->$field && $measurement2->$field) {
                $change = $measurement2->$field - $measurement1->$field;
                $changePercentage = ($change / $measurement1->$field) * 100;

                $comparison[$field] = [
                    'first' => $measurement1->$field,
                    'first_date' => $measurement1->measurement_date->format('Y-m-d'),
                    'second' => $measurement2->$field,
                    'second_date' => $measurement2->measurement_date->format('Y-m-d'),
                    'change' => round($change, 1),
                    'change_percentage' => round($changePercentage, 1),
                ];
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'measurement_1' => $measurement1->load(['measurer']),
                'measurement_2' => $measurement2->load(['measurer']),
                'comparison' => $comparison,
            ]
        ]);
    }
}
