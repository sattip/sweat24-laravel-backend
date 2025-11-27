<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PayrollAgreement;
use App\Models\Instructor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PayrollAgreementController extends Controller
{
    /**
     * List all payroll agreements, optionally filtered by instructor.
     */
    public function index(Request $request): JsonResponse
    {
        $query = PayrollAgreement::with('instructor');

        if ($request->has('instructor_id')) {
            $query->where('instructor_id', $request->instructor_id);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        $agreements = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $agreements->map(function ($agreement) {
                return [
                    'id' => $agreement->id,
                    'instructorId' => (string) $agreement->instructor_id,
                    'instructorName' => $agreement->instructor_name ?? $agreement->instructor?->name ?? 'Άγνωστος',
                    'description' => $agreement->description,
                    'type' => $agreement->type,
                    'amount' => (float) $agreement->amount,
                    'isRecurring' => $agreement->is_recurring,
                    'startDate' => $agreement->start_date->format('Y-m-d'),
                    'endDate' => $agreement->end_date?->format('Y-m-d'),
                    'isActive' => $agreement->is_active,
                    'createdAt' => $agreement->created_at->toISOString(),
                ];
            }),
        ]);
    }

    /**
     * Create a new payroll agreement.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'instructor_id' => 'required|exists:instructors,id',
            'description' => 'required|string|max:500',
            'type' => 'required|in:bonus,deduction,special_rate,hourly_rate',
            'amount' => 'required|numeric|min:0.01',
            'is_recurring' => 'boolean',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $instructor = Instructor::find($validated['instructor_id']);

        $agreement = PayrollAgreement::create([
            'instructor_id' => $validated['instructor_id'],
            'instructor_name' => $instructor->name,
            'description' => $validated['description'],
            'type' => $validated['type'],
            'amount' => $validated['amount'],
            'is_recurring' => $validated['is_recurring'] ?? true,
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'] ?? null,
            'is_active' => true,
        ]);

        $agreement->load('instructor');

        return response()->json([
            'success' => true,
            'message' => 'Η συμφωνία δημιουργήθηκε επιτυχώς',
            'data' => [
                'id' => $agreement->id,
                'instructorId' => (string) $agreement->instructor_id,
                'instructorName' => $agreement->instructor?->name ?? 'Άγνωστος',
                'description' => $agreement->description,
                'type' => $agreement->type,
                'amount' => (float) $agreement->amount,
                'isRecurring' => $agreement->is_recurring,
                'startDate' => $agreement->start_date->format('Y-m-d'),
                'endDate' => $agreement->end_date?->format('Y-m-d'),
                'isActive' => $agreement->is_active,
                'createdAt' => $agreement->created_at->toISOString(),
            ],
        ], 201);
    }

    /**
     * Update a payroll agreement.
     */
    public function update(Request $request, PayrollAgreement $payrollAgreement): JsonResponse
    {
        $validated = $request->validate([
            'description' => 'sometimes|required|string|max:500',
            'type' => 'sometimes|required|in:bonus,deduction,special_rate,hourly_rate',
            'amount' => 'sometimes|required|numeric|min:0.01',
            'is_recurring' => 'sometimes|boolean',
            'start_date' => 'sometimes|required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'is_active' => 'sometimes|boolean',
        ]);

        $payrollAgreement->update($validated);
        $payrollAgreement->load('instructor');

        return response()->json([
            'success' => true,
            'message' => 'Η συμφωνία ενημερώθηκε επιτυχώς',
            'data' => [
                'id' => $payrollAgreement->id,
                'instructorId' => (string) $payrollAgreement->instructor_id,
                'instructorName' => $payrollAgreement->instructor?->name ?? 'Άγνωστος',
                'description' => $payrollAgreement->description,
                'type' => $payrollAgreement->type,
                'amount' => (float) $payrollAgreement->amount,
                'isRecurring' => $payrollAgreement->is_recurring,
                'startDate' => $payrollAgreement->start_date->format('Y-m-d'),
                'endDate' => $payrollAgreement->end_date?->format('Y-m-d'),
                'isActive' => $payrollAgreement->is_active,
                'createdAt' => $payrollAgreement->created_at->toISOString(),
            ],
        ]);
    }

    /**
     * Toggle active status of a payroll agreement.
     */
    public function toggleActive(PayrollAgreement $payrollAgreement): JsonResponse
    {
        $payrollAgreement->update([
            'is_active' => !$payrollAgreement->is_active,
        ]);

        return response()->json([
            'success' => true,
            'message' => $payrollAgreement->is_active
                ? 'Η συμφωνία ενεργοποιήθηκε'
                : 'Η συμφωνία απενεργοποιήθηκε',
            'data' => [
                'id' => $payrollAgreement->id,
                'isActive' => $payrollAgreement->is_active,
            ],
        ]);
    }

    /**
     * Delete a payroll agreement.
     */
    public function destroy(PayrollAgreement $payrollAgreement): JsonResponse
    {
        $payrollAgreement->delete();

        return response()->json([
            'success' => true,
            'message' => 'Η συμφωνία διαγράφηκε επιτυχώς',
        ]);
    }

    /**
     * Get summary of active agreements for an instructor.
     */
    public function summary(int $instructorId): JsonResponse
    {
        $instructor = Instructor::find($instructorId);

        if (!$instructor) {
            return response()->json([
                'success' => false,
                'message' => 'Ο προπονητής δεν βρέθηκε',
            ], 404);
        }

        $activeAgreements = PayrollAgreement::where('instructor_id', $instructorId)
            ->where('is_active', true)
            ->get();

        $monthlyTotal = $activeAgreements
            ->where('is_recurring', true)
            ->reduce(function ($total, $agreement) {
                if ($agreement->type === 'deduction') {
                    return $total - $agreement->amount;
                }
                return $total + $agreement->amount;
            }, 0);

        $hourlyRate = $activeAgreements
            ->where('type', 'hourly_rate')
            ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'instructorId' => $instructorId,
                'instructorName' => $instructor->name,
                'monthlyTotal' => (float) $monthlyTotal,
                'hourlyRate' => $hourlyRate ? (float) $hourlyRate->amount : null,
                'activeAgreementsCount' => $activeAgreements->count(),
            ],
        ]);
    }
}
