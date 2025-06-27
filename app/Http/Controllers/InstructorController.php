<?php

namespace App\Http\Controllers;

use App\Models\Instructor;
use Illuminate\Http\Request;

class InstructorController extends Controller
{
    public function index()
    {
        $instructors = Instructor::with('workTimeEntries', 'payrollAgreements')->get();
        return response()->json($instructors);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'specialties' => 'required|array',
            'email' => 'nullable|email|unique:instructors',
            'phone' => 'nullable|string',
            'hourly_rate' => 'required|numeric|min:0',
            'monthly_bonus' => 'nullable|numeric|min:0',
            'commission_rate' => 'nullable|numeric|min:0|max:1',
            'contract_type' => 'required|in:hourly,salary,commission',
            'status' => 'sometimes|in:active,inactive,vacation',
            'join_date' => 'required|date',
            'total_revenue' => 'sometimes|numeric|min:0',
            'completed_sessions' => 'sometimes|integer|min:0',
        ]);

        $instructor = Instructor::create($validated);
        return response()->json($instructor, 201);
    }

    public function show(Instructor $instructor)
    {
        return response()->json($instructor->load('workTimeEntries', 'payrollAgreements'));
    }

    public function update(Request $request, Instructor $instructor)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'specialties' => 'sometimes|array',
            'email' => 'nullable|email|unique:instructors,email,' . $instructor->id,
            'phone' => 'nullable|string',
            'hourly_rate' => 'sometimes|numeric|min:0',
            'monthly_bonus' => 'nullable|numeric|min:0',
            'commission_rate' => 'nullable|numeric|min:0|max:1',
            'contract_type' => 'sometimes|in:hourly,salary,commission',
            'status' => 'sometimes|in:active,inactive,vacation',
            'join_date' => 'sometimes|date',
            'total_revenue' => 'sometimes|numeric|min:0',
            'completed_sessions' => 'sometimes|integer|min:0',
        ]);

        $instructor->update($validated);
        return response()->json($instructor);
    }

    public function destroy(Instructor $instructor)
    {
        $instructor->delete();
        return response()->json(['message' => 'Instructor deleted successfully']);
    }
}