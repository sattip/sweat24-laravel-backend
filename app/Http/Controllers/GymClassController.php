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
        
        if ($request->has('instructor')) {
            $query->where('instructor_id', $request->instructor);
        }
        
        $classes = $query->orderBy('date')->orderBy('time')->get();
        
        // Map instructor IDs to names
        $instructors = \App\Models\Instructor::pluck('name', 'id');
        
        $classes->transform(function($class) use ($instructors) {
            // Map instructor ID to name
            $instructorId = $class->instructor;
            $class->instructor_name = $instructors[$instructorId] ?? 'Χωρίς Προπονητή';
            $class->trainer_id = $instructorId;
            $class->trainer_name = $class->instructor_name;
            
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
            'date' => 'required|date|after_or_equal:today',
            'time' => 'required|string',
            'duration' => 'required|integer|min:1',
            'max_participants' => 'required|integer|min:1',
            'location' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);
        
        $validated['status'] = 'active';
        $validated['current_participants'] = 0;
        
        // Ensure description is not null
        if (!isset($validated['description']) || $validated['description'] === null) {
            $validated['description'] = '';
        }
        
        $class = GymClass::create($validated);
        
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
        ]);
        
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
}
