<?php

namespace App\Http\Controllers;

use App\Models\AppointmentRequest;
use App\Models\SpecializedService;
use Illuminate\Http\Request;

class AppointmentRequestController extends Controller
{
    public function index()
    {
        $requests = AppointmentRequest::with(['specializedService'])
            ->orderBy('created_at', 'desc')
            ->get();
        
        // Map to include service info
        $mappedRequests = $requests->map(function ($request) {
            return [
                'id' => $request->id,
                'specialized_service_id' => $request->specialized_service_id,
                'name' => $request->name,
                'phone' => $request->phone,
                'preferred_time_slot' => $request->preferred_time_slot,
                'notes' => $request->notes,
                'status' => $request->status,
                'created_at' => $request->created_at,
                'service' => $request->specializedService,
            ];
        });
        
        return response()->json($mappedRequests);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'specialized_service_id' => 'required|exists:specialized_services,id',
            'name' => 'required|string|max:255',
            'phone' => 'required|string',
            'preferred_time_slot' => 'required|string|in:morning,afternoon,evening',
            'notes' => 'nullable|string',
        ]);

        // Default status
        $validated['status'] = 'pending';

        $appointmentRequest = AppointmentRequest::create($validated);
        
        return response()->json($appointmentRequest, 201);
    }

    public function show(AppointmentRequest $appointmentRequest)
    {
        return response()->json($appointmentRequest->load(['user', 'specializedService', 'instructor']));
    }

    public function update(Request $request, AppointmentRequest $appointmentRequest)
    {
        $validated = $request->validate([
            'status' => 'sometimes|in:pending,confirmed,cancelled,completed',
            'instructor_id' => 'nullable|exists:instructors,id',
            'confirmed_date' => 'nullable|date',
            'confirmed_time' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $appointmentRequest->update($validated);
        
        return response()->json($appointmentRequest->load(['user', 'specializedService', 'instructor']));
    }

    public function destroy(AppointmentRequest $appointmentRequest)
    {
        $appointmentRequest->delete();
        return response()->json(['message' => 'Appointment request deleted successfully']);
    }
}
