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
        $query = GymClass::with('instructor');
        
        if ($request->has('date')) {
            $query->whereDate('date', $request->date);
        }
        
        if ($request->has('instructor')) {
            $query->where('instructor_id', $request->instructor);
        }
        
        $classes = $query->orderBy('date')->orderBy('time')->get();
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
            'instructor_id' => 'required|exists:instructors,id',
            'date' => 'required|date',
            'time' => 'required|string',
            'duration' => 'required|integer|min:1',
            'max_participants' => 'required|integer|min:1',
            'location' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);
        
        $validated['status'] = 'active';
        $validated['current_participants'] = 0;
        
        $class = GymClass::create($validated);
        return response()->json($class->load('instructor'), 201);
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
            'instructor_id' => 'sometimes|exists:instructors,id',
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
        return response()->json($class->load('instructor'));
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
