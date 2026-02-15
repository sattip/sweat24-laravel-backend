<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TrialAppointment;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Traits\ApiResponseTrait;
use App\Http\Requests\StoreTrialAppointmentRequest;
use Carbon\Carbon;

class TrialAppointmentController extends Controller
{
    use ApiResponseTrait;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $query = TrialAppointment::with(['user', 'service', 'instructor']);

            // Φιλτράρισμα ανάλογα με το ρόλο του χρήστη
            $user = $request->user();
            if ($user->isMember()) {
                $query->where('user_id', $user->id);
            }

            // Φιλτράρισμα κατά status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Φιλτράρισμα κατά service
            if ($request->has('service_id')) {
                $query->where('service_id', $request->service_id);
            }

            // Φιλτράρισμα κατά ημερομηνία
            if ($request->has('date_from')) {
                $query->where('appointment_date', '>=', $request->date_from);
            }

            if ($request->has('date_to')) {
                $query->where('appointment_date', '<=', $request->date_to);
            }

            // Ταξινόμηση
            $query->orderBy('appointment_date', 'desc')
                  ->orderBy('appointment_time', 'desc');

            $appointments = $query->paginate($request->get('per_page', 15));

            return $this->successResponse($appointments, 'Δοκιμαστικά ραντεβού ανακτήθηκαν επιτυχώς');
        } catch (\Exception $e) {
            return $this->errorResponse('Σφάλμα κατά την ανάκτηση δοκιμαστικών ραντεβού', 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTrialAppointmentRequest $request)
    {
        try {
            $user = $request->user();
            $service = Service::findOrFail($request->service_id);

            // Δημιουργία του δοκιμαστικού ραντεβού
            $appointment = TrialAppointment::create([
                'user_id' => $user->id,
                'service_id' => $request->service_id,
                'appointment_date' => $request->appointment_date,
                'appointment_time' => $request->appointment_time,
                'price' => $service->trial_price,
                'notes' => $request->notes,
                'status' => 'pending',
            ]);

            return $this->successResponse(
                $appointment->load(['user', 'service', 'instructor']),
                'Το δοκιμαστικό ραντεβού δημιουργήθηκε επιτυχώς',
                201
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Σφάλμα κατά τη δημιουργία δοκιμαστικού ραντεβού', 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(TrialAppointment $appointment)
    {
        try {
            // Έλεγχος δικαιωμάτων - μόνο ο χρήστης ή admin μπορούν να δουν το ραντεβού
            $user = request()->user();
            if ($user->isMember() && $appointment->user_id !== $user->id) {
                return $this->errorResponse('Δεν έχετε δικαίωμα να δείτε αυτό το ραντεβού', 403);
            }

            return $this->successResponse(
                $appointment->load(['user', 'service', 'instructor']),
                'Το δοκιμαστικό ραντεβού ανακτήθηκε επιτυχώς'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Σφάλμα κατά την ανάκτηση δοκιμαστικού ραντεβού', 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TrialAppointment $appointment)
    {
        try {
            // Έλεγχος δικαιωμάτων
            $user = $request->user();
            if ($user->isMember() && $appointment->user_id !== $user->id) {
                return $this->errorResponse('Δεν έχετε δικαίωμα να ενημερώσετε αυτό το ραντεβού', 403);
            }

            $validator = Validator::make($request->all(), [
                'appointment_date' => 'sometimes|required|date|after_or_equal:today',
                'appointment_time' => 'sometimes|required|date_format:H:i',
                'notes' => 'nullable|string|max:1000',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            // Δεν επιτρέπεται η αλλαγή ημερομηνίας/ώρας αν το ραντεβού είναι confirmed ή completed
            if (in_array($appointment->status, ['confirmed', 'completed']) && ($request->has('appointment_date') || $request->has('appointment_time'))) {
                return $this->errorResponse('Δεν μπορείτε να αλλάξετε ημερομηνία/ώρα ενός επιβεβαιωμένου ή ολοκληρωμένου ραντεβού', 400);
            }

            $appointment->update($request->only(['appointment_date', 'appointment_time', 'notes']));

            return $this->successResponse(
                $appointment->load(['user', 'service', 'instructor']),
                'Το δοκιμαστικό ραντεβού ενημερώθηκε επιτυχώς'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Σφάλμα κατά την ενημέρωση δοκιμαστικού ραντεβού', 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TrialAppointment $appointment)
    {
        try {
            // Έλεγχος δικαιωμάτων
            $user = request()->user();
            if ($user->isMember() && $appointment->user_id !== $user->id) {
                return $this->errorResponse('Δεν έχετε δικαίωμα να διαγράψετε αυτό το ραντεβού', 403);
            }

            // Δεν επιτρέπεται η διαγραφή αν το ραντεβού είναι completed
            if ($appointment->status === 'completed') {
                return $this->errorResponse('Δεν μπορείτε να διαγράψετε ένα ολοκληρωμένο ραντεβού', 400);
            }

            $appointment->delete();

            return $this->successResponse(null, 'Το δοκιμαστικό ραντεβού διαγράφηκε επιτυχώς');
        } catch (\Exception $e) {
            return $this->errorResponse('Σφάλμα κατά τη διαγραφή δοκιμαστικού ραντεβού', 500);
        }
    }

    /**
     * Confirm an appointment.
     */
    public function confirm(TrialAppointment $appointment)
    {
        try {
            // Μόνο admin/trainer μπορούν να επιβεβαιώσουν ραντεβού
            $user = request()->user();
            if (!$user->isAdmin() && !$user->isTrainer()) {
                return $this->errorResponse('Δεν έχετε δικαίωμα να επιβεβαιώσετε αυτό το ραντεβού', 403);
            }

            if ($appointment->status !== 'pending') {
                return $this->errorResponse('Μόνο εκκρεμή ραντεβού μπορούν να επιβεβαιωθούν', 400);
            }

            $appointment->confirm();

            return $this->successResponse(
                $appointment->load(['user', 'service', 'instructor']),
                'Το δοκιμαστικό ραντεβού επιβεβαιώθηκε επιτυχώς'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Σφάλμα κατά την επιβεβαίωση δοκιμαστικού ραντεβού', 500);
        }
    }

    /**
     * Cancel an appointment.
     */
    public function cancel(TrialAppointment $appointment)
    {
        try {
            $user = request()->user();

            // Μέλη μπορούν να ακυρώσουν μόνο τα δικά τους ραντεβού
            if ($user->isMember() && $appointment->user_id !== $user->id) {
                return $this->errorResponse('Δεν έχετε δικαίωμα να ακυρώσετε αυτό το ραντεβού', 403);
            }

            if (in_array($appointment->status, ['completed', 'cancelled'])) {
                return $this->errorResponse('Αυτό το ραντεβού δεν μπορεί να ακυρωθεί', 400);
            }

            $appointment->cancel();

            return $this->successResponse(
                $appointment->load(['user', 'service', 'instructor']),
                'Το δοκιμαστικό ραντεβού ακυρώθηκε επιτυχώς'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Σφάλμα κατά την ακύρωση δοκιμαστικού ραντεβού', 500);
        }
    }

    /**
     * Mark appointment as completed.
     */
    public function complete(TrialAppointment $appointment)
    {
        try {
            // Μόνο admin/trainer μπορούν να ολοκληρώσουν ραντεβού
            $user = request()->user();
            if (!$user->isAdmin() && !$user->isTrainer()) {
                return $this->errorResponse('Δεν έχετε δικαίωμα να ολοκληρώσετε αυτό το ραντεβού', 403);
            }

            if ($appointment->status !== 'confirmed') {
                return $this->errorResponse('Μόνο επιβεβαιωμένα ραντεβού μπορούν να ολοκληρωθούν', 400);
            }

            $appointment->complete();

            return $this->successResponse(
                $appointment->load(['user', 'service', 'instructor']),
                'Το δοκιμαστικό ραντεβού ολοκληρώθηκε επιτυχώς'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Σφάλμα κατά την ολοκλήρωση δοκιμαστικού ραντεβού', 500);
        }
    }

    /**
     * Get user's trial appointments.
     */
    public function getUserTrials(Request $request)
    {
        try {
            $user = $request->user();

            $query = TrialAppointment::with(['service', 'instructor'])
                ->where('user_id', $user->id);

            // Φιλτράρισμα κατά status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            $appointments = $query->orderBy('appointment_date', 'desc')
                                 ->orderBy('appointment_time', 'desc')
                                 ->paginate($request->get('per_page', 15));

            return $this->successResponse($appointments, 'Τα δοκιμαστικά ραντεβού σας ανακτήθηκαν επιτυχώς');
        } catch (\Exception $e) {
            return $this->errorResponse('Σφάλμα κατά την ανάκτηση δοκιμαστικών ραντεβού', 500);
        }
    }
}
