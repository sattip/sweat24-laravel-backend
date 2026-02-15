<?php

namespace App\Http\Controllers;

use App\Models\GymClass;
use Illuminate\Http\Request;

class GymClassController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = GymClass::query();
        
        // Filter to show only future classes (from current date and time)
        $now = now();
        $query->where(function($q) use ($now) {
            $q->where('date', '>', $now->toDateString())
              ->orWhere(function($subQuery) use ($now) {
                  $subQuery->where('date', '=', $now->toDateString())
                           ->whereRaw("CONCAT(date, ' ', time) > ?", [$now->toDateTimeString()]);
              });
        });
        
        if ($request->has('date')) {
            $query->whereDate('date', $request->date);
        }
        
        if ($request->has('instructor') && $request->instructor) {
            // Handle both string and integer instructor IDs
            $query->where('instructor', '=', (string)$request->instructor);
        }
        
        $classes = $query->with('service')->orderBy('date')->orderBy('time')->get();

        // Map instructor IDs to names
        $instructors = \App\Models\Instructor::pluck('name', 'id');

        $classes->transform(function($class) use ($instructors) {
            // Map instructor ID to name
            $instructorId = $class->instructor;
            $class->instructor_name = $instructors[$instructorId] ?? 'Χωρίς Προπονητή';
            $class->trainer_id = $instructorId;
            $class->trainer_name = $class->instructor_name;

            // Add service information
            $class->service_id = $class->service_id;
            $class->service = $class->service ? [
                'id' => $class->service->id,
                'name' => $class->service->name,
                'slug' => $class->service->slug
            ] : null;

            // Ensure we send the actual class name, not type
            $class->class_type = $class->name; // Send the class name as class_type for the calendar

            // Format times properly
            $class->start_time = $class->time;
            $class->end_time = \Carbon\Carbon::parse($class->time)->addMinutes($class->duration)->format('H:i');

            return $class;
        });
        
        return response()->json($classes);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string',
            'instructor' => 'required|exists:instructors,id',
            'service_id' => 'nullable|exists:services,id',
            'date' => 'required|date|after_or_equal:today',
            'time' => 'required|string',
            'duration' => 'required|integer|min:1',
            'max_participants' => 'required|integer|min:1',
            'location' => 'required|string|max:255',
            'description' => 'nullable|string',
            'cancellation_policy_id' => 'nullable|exists:cancellation_policies,id',
        ]);
        
        $validated['status'] = 'active';
        $validated['current_participants'] = 0;
        
        // Ensure description is not null
        if (!isset($validated['description']) || $validated['description'] === null) {
            $validated['description'] = '';
        }
        
        $class = GymClass::create($validated);

        // Load service relationship
        $class->load('service');

        // Transform the response to include all necessary fields
        $instructors = \App\Models\Instructor::pluck('name', 'id');

        // Create a new array with the transformed data
        $response = [
            'id' => $class->id,
            'name' => $class->name,
            'type' => $class->type,
            'instructor' => $class->instructor,
            'service_id' => $class->service_id,
            'service' => $class->service ? [
                'id' => $class->service->id,
                'name' => $class->service->name,
                'slug' => $class->service->slug
            ] : null,
            'date' => $class->date->format('Y-m-d'),
            'time' => $class->time,
            'duration' => $class->duration,
            'max_participants' => $class->max_participants,
            'current_participants' => $class->current_participants,
            'location' => $class->location,
            'description' => $class->description,
            'status' => $class->status,
            'created_at' => $class->created_at,
            'updated_at' => $class->updated_at,
            // Additional fields for the frontend
            'instructor_name' => $instructors[$class->instructor] ?? 'Χωρίς Προπονητή',
            'trainer_id' => $class->instructor,
            'trainer_name' => $instructors[$class->instructor] ?? 'Χωρίς Προπονητή',
            'class_type' => $class->name,
            'start_time' => $class->time,
            'end_time' => \Carbon\Carbon::parse($class->time)->addMinutes($class->duration)->format('H:i')
        ];

        return response()->json($response, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(GymClass $class)
    {
        return response()->json($class->load('instructor'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, GymClass $class)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'type' => 'sometimes|string',
            'instructor' => 'sometimes|exists:instructors,id',
            'date' => 'sometimes|date',
            'time' => 'sometimes|string',
            'duration' => 'sometimes|integer|min:1',
            'max_participants' => 'sometimes|integer|min:1',
            'current_participants' => 'sometimes|integer|min:0',
            'location' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'status' => 'sometimes|in:active,cancelled,completed',
            'cancellation_policy_id' => 'nullable|exists:cancellation_policies,id',
        ]);
        
        // Remove null description to prevent NOT NULL constraint violation
        if (array_key_exists('description', $validated) && is_null($validated['description'])) {
            unset($validated['description']);
        }
        
        $class->update($validated);
        
        // Transform the response to include all necessary fields
        $instructors = \App\Models\Instructor::pluck('name', 'id');
        
        // Create a new array with the transformed data
        $response = [
            'id' => $class->id,
            'name' => $class->name,
            'type' => $class->type,
            'instructor' => $class->instructor,
            'date' => $class->date->format('Y-m-d'),
            'time' => $class->time,
            'duration' => $class->duration,
            'max_participants' => $class->max_participants,
            'current_participants' => $class->current_participants,
            'location' => $class->location,
            'description' => $class->description,
            'status' => $class->status,
            'created_at' => $class->created_at,
            'updated_at' => $class->updated_at,
            // Additional fields for the frontend
            'instructor_name' => $instructors[$class->instructor] ?? 'Χωρίς Προπονητή',
            'trainer_id' => $class->instructor,
            'trainer_name' => $instructors[$class->instructor] ?? 'Χωρίς Προπονητή',
            'class_type' => $class->name,
            'start_time' => $class->time,
            'end_time' => \Carbon\Carbon::parse($class->time)->addMinutes($class->duration)->format('H:i')
        ];
        
        return response()->json($response);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(GymClass $class)
    {
        $class->delete();
        return response()->json(['message' => 'Class deleted successfully']);
    }
    
    /**
     * Delete recurring classes based on scope
     */
    public function deleteRecurring(Request $request, GymClass $class)
    {
        $validated = $request->validate([
            'scope' => 'required|in:day,week,month,all',
            'date' => 'nullable|date'
        ]);
        
        $scope = $validated['scope'];
        $referenceDate = $validated['date'] ?? $class->date->toDateString();
        $referenceDateCarbon = \Carbon\Carbon::parse($referenceDate);
        
        // Build base query for related classes (same "series")
        $query = GymClass::where('name', $class->name)
            ->where('type', $class->type)
            ->where('instructor', $class->instructor)
            ->where('time', $class->time)
            ->where('location', $class->location);
        
        // Apply scope-based date filtering
        switch ($scope) {
            case 'day':
                $query->whereDate('date', $referenceDateCarbon->toDateString());
                break;
                
            case 'week':
                $startOfWeek = $referenceDateCarbon->copy()->startOfWeek();
                $endOfWeek = $referenceDateCarbon->copy()->endOfWeek();
                $query->whereBetween('date', [$startOfWeek->toDateString(), $endOfWeek->toDateString()]);
                break;
                
            case 'month':
                $query->whereYear('date', $referenceDateCarbon->year)
                      ->whereMonth('date', $referenceDateCarbon->month);
                break;
                
            case 'all':
                // Delete all future occurrences (including today)
                $query->where('date', '>=', $referenceDateCarbon->toDateString());
                break;
        }
        
        // Execute the deletion
        $classesToDelete = $query->get();
        $deletedCount = $classesToDelete->count();
        
        // Check for existing bookings before deletion
        $classesWithBookings = [];
        foreach ($classesToDelete as $classToDelete) {
            $bookingCount = $classToDelete->bookings()->count();
            if ($bookingCount > 0) {
                $classesWithBookings[] = [
                    'id' => $classToDelete->id,
                    'date' => $classToDelete->date->format('Y-m-d'),
                    'time' => $classToDelete->time,
                    'bookings' => $bookingCount
                ];
            }
        }
        
        // If there are bookings, return warning but still allow deletion
        if (!empty($classesWithBookings)) {
            $response = [
                'message' => "Βρέθηκαν {$deletedCount} μαθήματα για διαγραφή",
                'deleted_count' => $deletedCount,
                'scope' => $scope,
                'reference_date' => $referenceDate,
                'classes_with_bookings' => $classesWithBookings,
                'warning' => 'Κάποια μαθήματα έχουν κρατήσεις που θα ακυρωθούν'
            ];
        } else {
            $response = [
                'message' => "Διαγράφηκαν επιτυχώς {$deletedCount} μαθήματα",
                'deleted_count' => $deletedCount,
                'scope' => $scope,
                'reference_date' => $referenceDate
            ];
        }
        
        // Perform the actual deletion
        $query = GymClass::where('name', $class->name)
            ->where('type', $class->type)
            ->where('instructor', $class->instructor)
            ->where('time', $class->time)
            ->where('location', $class->location);
            
        // Reapply scope filtering for deletion
        switch ($scope) {
            case 'day':
                $query->whereDate('date', $referenceDateCarbon->toDateString());
                break;
            case 'week':
                $startOfWeek = $referenceDateCarbon->copy()->startOfWeek();
                $endOfWeek = $referenceDateCarbon->copy()->endOfWeek();
                $query->whereBetween('date', [$startOfWeek->toDateString(), $endOfWeek->toDateString()]);
                break;
            case 'month':
                $query->whereYear('date', $referenceDateCarbon->year)
                      ->whereMonth('date', $referenceDateCarbon->month);
                break;
            case 'all':
                $query->where('date', '>=', $referenceDateCarbon->toDateString());
                break;
        }
        
        $query->delete();
        
        return response()->json($response);
    }
    
    /**
     * Preview recurring class deletion (dry run)
     */
    public function previewRecurringDeletion(Request $request, GymClass $class)
    {
        $validated = $request->validate([
            'scope' => 'required|in:day,week,month,all',
            'date' => 'nullable|date'
        ]);
        
        $scope = $validated['scope'];
        $referenceDate = $validated['date'] ?? $class->date->toDateString();
        $referenceDateCarbon = \Carbon\Carbon::parse($referenceDate);
        
        // Build base query for related classes (same "series")
        $query = GymClass::where('name', $class->name)
            ->where('type', $class->type)
            ->where('instructor', $class->instructor)
            ->where('time', $class->time)
            ->where('location', $class->location);
        
        // Apply scope-based date filtering
        switch ($scope) {
            case 'day':
                $query->whereDate('date', $referenceDateCarbon->toDateString());
                break;
            case 'week':
                $startOfWeek = $referenceDateCarbon->copy()->startOfWeek();
                $endOfWeek = $referenceDateCarbon->copy()->endOfWeek();
                $query->whereBetween('date', [$startOfWeek->toDateString(), $endOfWeek->toDateString()]);
                break;
            case 'month':
                $query->whereYear('date', $referenceDateCarbon->year)
                      ->whereMonth('date', $referenceDateCarbon->month);
                break;
            case 'all':
                $query->where('date', '>=', $referenceDateCarbon->toDateString());
                break;
        }
        
        $classesToDelete = $query->get();
        $deletedCount = $classesToDelete->count();
        
        // Get detailed info about each class to be deleted
        $classDetails = [];
        $totalBookings = 0;
        
        foreach ($classesToDelete as $classToDelete) {
            $bookingCount = $classToDelete->bookings()->count();
            $totalBookings += $bookingCount;
            
            $classDetails[] = [
                'id' => $classToDelete->id,
                'date' => $classToDelete->date->format('Y-m-d'),
                'time' => $classToDelete->time,
                'bookings' => $bookingCount,
                'current_participants' => $classToDelete->current_participants,
                'max_participants' => $classToDelete->max_participants
            ];
        }
        
        return response()->json([
            'preview' => true,
            'class_series' => [
                'name' => $class->name,
                'type' => $class->type,
                'instructor' => $class->instructor,
                'time' => $class->time,
                'location' => $class->location
            ],
            'scope' => $scope,
            'reference_date' => $referenceDate,
            'classes_to_delete' => $classDetails,
            'total_classes' => $deletedCount,
            'total_bookings_affected' => $totalBookings,
            'has_bookings' => $totalBookings > 0,
            'warning' => $totalBookings > 0 ? "Θα ακυρωθούν {$totalBookings} κρατήσεις" : null
        ]);
    }
    
    /**
     * Get recurring status and related classes info
     */
    public function getRecurringInfo(GymClass $class)
    {
        // Find all related classes (same series)
        $relatedClasses = GymClass::where('name', $class->name)
            ->where('type', $class->type)
            ->where('instructor', $class->instructor)
            ->where('time', $class->time)
            ->where('location', $class->location)
            ->where('date', '>=', now()->toDateString()) // Only future classes
            ->orderBy('date')
            ->get();
        
        $isRecurring = $relatedClasses->count() > 1;
        
        $recurringInfo = [];
        if ($isRecurring) {
            foreach ($relatedClasses as $relatedClass) {
                $recurringInfo[] = [
                    'id' => $relatedClass->id,
                    'date' => $relatedClass->date->format('Y-m-d'),
                    'time' => $relatedClass->time,
                    'bookings' => $relatedClass->bookings()->count(),
                    'current_participants' => $relatedClass->current_participants,
                    'is_current' => $relatedClass->id === $class->id
                ];
            }
        }
        
        return response()->json([
            'class_id' => $class->id,
            'is_recurring' => $isRecurring,
            'total_related_classes' => $relatedClasses->count(),
            'class_series' => [
                'name' => $class->name,
                'type' => $class->type,
                'instructor' => $class->instructor,
                'time' => $class->time,
                'location' => $class->location
            ],
            'related_classes' => $recurringInfo
        ]);
    }
}
