<?php

namespace App\Http\Controllers;

use App\Models\GymClass;
use App\Models\Instructor;
use Illuminate\Http\Request;

class ClassController extends Controller
{
    public function index(Request $request)
    {
        $query = GymClass::with('instructor');
        
        if ($request->has('date')) {
            $query->whereDate('date', $request->date);
        } else {
            // Default to today
            $query->whereDate('date', now()->format('Y-m-d'));
        }
        
        if ($request->has('instructor') && $request->instructor) {
            $query->where('instructor_id', $request->instructor);
        }
        
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }
        
        $classes = $query->orderBy('date')->orderBy('time')->get();
        $instructors = Instructor::where('status', 'active')->orderBy('name')->get();
        
        return view('admin.classes.index', compact('classes', 'instructors'));
    }
}