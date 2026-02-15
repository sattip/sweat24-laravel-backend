<?php

namespace App\Http\Controllers;

use App\Models\AppointmentRequest;
use App\Models\SpecializedService;
use Illuminate\Http\Request;

class AppointmentRequestController extends Controller
{
    public function index()
    {
        $requests = AppointmentRequest::with(['specializedService', 'service'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Map to include service info
        $mappedRequests = $requests->map(function ($request) {
            return [
                'id' => $request->id,
                'specialized_service_id' => $request->specialized_service_id,
                'service_id' => $request->service_id,
                'name' => $request->client_name,
                'phone' => $request->client_phone,
                'preferred_time_slots' => $request->preferred_time_slots,
                'notes' => $request->notes,
                'status' => $request->status,
                'created_at' => $request->created_at,
                'specialized_service' => $request->specializedService,
                'service' => $request->service,
            ];
        });

        return response()->json($mappedRequests);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'specialized_service_id' => 'required|exists:specialized_services,id',
            'service_id' => 'nullable|exists:services,id',
            'client_name' => 'required|string|max:255',
            'client_phone' => 'required|string',
            'client_email' => 'nullable|email|max:255',
            'preferred_time_slots' => 'required|json',
            'notes' => 'nullable|string',
        ]);

        // Default status
        $validated['status'] = 'pending';

        $appointmentRequest = AppointmentRequest::create($validated);
        $appointmentRequest->load(['specializedService', 'service']);

        return response()->json($appointmentRequest, 201);
    }

    public function show(AppointmentRequest $appointmentRequest)
    {
        return response()->json($appointmentRequest->load(['user', 'specializedService', 'instructor', 'service']));
    }

    public function update(Request $request, AppointmentRequest $appointmentRequest)
    {
        $validated = $request->validate([
            'service_id' => 'sometimes|exists:services,id',
            'status' => 'sometimes|in:pending,confirmed,cancelled,completed',
            'instructor_id' => 'nullable|exists:instructors,id',
            'confirmed_date' => 'nullable|date',
            'confirmed_time' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $appointmentRequest->update($validated);
        
        return response()->json($appointmentRequest->load(['user', 'specializedService', 'instructor', 'service']));
    }

    public function destroy(AppointmentRequest $appointmentRequest)
    {
        $appointmentRequest->delete();
        return response()->json(['message' => 'Appointment request deleted successfully']);
    }
}
