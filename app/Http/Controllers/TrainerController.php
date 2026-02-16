<?php

namespace App\Http\Controllers;

use App\Models\Instructor;
use Illuminate\Http\Request;

class TrainerController extends Controller
{
    // Admin view
    public function index(Request $request)
    {
        $trainers = Instructor::with(['workTimeEntries', 'payrollAgreements'])
                            ->orderBy('name')
                            ->get();
        
        return view('admin.trainers.index', compact('trainers'));
    }

    // API endpoints for client app
    public function apiIndex()
    {
        $trainers = Instructor::where('status', 'active')
                          ->orderBy('name')
                          ->get()
                          ->map(function ($instructor) {
                              return [
                                  'id' => $instructor->id,
                                  'slug' => $instructor->slug ?? \Illuminate\Support\Str::slug($instructor->name),
                                  'name' => $instructor->name,
                                  'title' => $instructor->bio ?? 'Προπονητής',
                                  'imageUrl' => $instructor->image_url ?? 'https://images.unsplash.com/photo-1581092795360-fd1ca04f0952',
                                  'bio' => $instructor->bio ?? '',
                                  'specialties' => $instructor->specialties ?? [],
                                  'certifications' => $instructor->certifications ?? [],
                                  'services' => $instructor->services ?? []
                              ];
                          });

        return response()->json($trainers);
    }

    public function apiShow($id)
    {
        $instructor = Instructor::find($id);

        if (!$instructor || $instructor->status !== 'active') {
            return response()->json(['message' => 'Trainer not found'], 404);
        }

        $trainer = [
            'id' => $instructor->id,
            'slug' => $instructor->slug ?? \Illuminate\Support\Str::slug($instructor->name),
            'name' => $instructor->name,
            'title' => $instructor->bio ?? 'Προπονητής',
            'imageUrl' => $instructor->image_url ?? 'https://images.unsplash.com/photo-1581092795360-fd1ca04f0952',
            'bio' => $instructor->bio ?? '',
            'specialties' => $instructor->specialties ?? [],
            'certifications' => $instructor->certifications ?? [],
            'services' => $instructor->services ?? []
        ];

        return response()->json($trainer);
    }
}