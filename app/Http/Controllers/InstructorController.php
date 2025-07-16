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
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'nullable|email|unique:instructors',
                'phone' => 'nullable|string',
                'specialties' => 'nullable',
                'certifications' => 'nullable|string',
                'experience' => 'nullable|string', 
                'bio' => 'nullable|string',
                'image_url' => 'nullable|string',
            ]);

            // Set defaults for required fields
            $validated['status'] = 'active';
            $validated['join_date'] = now();
            $validated['contract_type'] = 'hourly';
            $validated['hourly_rate'] = 0;
            $validated['total_revenue'] = 0;
            $validated['completed_sessions'] = 0;

            // Handle specialties - ensure it's an array
            if (isset($validated['specialties'])) {
                if (is_string($validated['specialties'])) {
                    $validated['specialties'] = json_decode($validated['specialties'], true) ?? [];
                } elseif (!is_array($validated['specialties'])) {
                    $validated['specialties'] = [];
                }
            } else {
                $validated['specialties'] = [];
            }

            // Handle certifications as array
            if (isset($validated['certifications']) && is_string($validated['certifications'])) {
                $validated['certifications'] = array_map('trim', explode(',', $validated['certifications']));
            } else {
                $validated['certifications'] = [];
            }

            // First create a User account for the instructor
            $temporaryPassword = 'sweat24' . rand(1000, 9999);
            $user = \App\Models\User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => bcrypt($temporaryPassword),
                'phone' => $validated['phone'] ?? null,
                'role' => 'trainer',
                'status' => 'active',
                'join_date' => $validated['join_date'],
            ]);
            
            // Create the instructor record
            $instructor = Instructor::create($validated);
            
            // Log the created credentials
            \Log::info('Trainer user created', [
                'instructor_id' => $instructor->id,
                'user_id' => $user->id,
                'email' => $user->email,
                'temporary_password' => $temporaryPassword
            ]);
            
            return response()->json([
                'instructor' => $instructor,
                'message' => 'Trainer created successfully. Login credentials: ' . $user->email . ' / ' . $temporaryPassword
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error creating instructor',
                'error' => $e->getMessage()
            ], 500);
        }
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