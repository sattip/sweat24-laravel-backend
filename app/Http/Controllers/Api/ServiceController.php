<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Traits\ApiResponseTrait;
use App\Http\Requests\StoreServiceRequest;
use App\Http\Requests\UpdateServiceRequest;

class ServiceController extends Controller
{
    use ApiResponseTrait;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $query = Service::query();

            // Φιλτράρισμα για ενεργές υπηρεσίες
            if ($request->has('active_only') && $request->active_only) {
                $query->active();
            }

            // Ταξινόμηση
            if ($request->has('sort_by')) {
                $sortDirection = $request->get('sort_direction', 'asc');
                $query->orderBy($request->sort_by, $sortDirection);
            } else {
                $query->ordered();
            }

            $services = $query->paginate($request->get('per_page', 15));

            return $this->successResponse($services, 'Υπηρεσίες ανακτήθηκαν επιτυχώς');
        } catch (\Exception $e) {
            return $this->errorResponse('Σφάλμα κατά την ανάκτηση υπηρεσιών', 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreServiceRequest $request)
    {
        try {
            $service = Service::create($request->validated());

            return $this->successResponse($service, 'Η υπηρεσία δημιουργήθηκε επιτυχώς', 201);
        } catch (\Exception $e) {
            return $this->errorResponse('Σφάλμα κατά τη δημιουργία υπηρεσίας', 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Service $service)
    {
        try {
            return $this->successResponse($service->load(['packages']), 'Η υπηρεσία ανακτήθηκε επιτυχώς');
        } catch (\Exception $e) {
            return $this->errorResponse('Σφάλμα κατά την ανάκτηση υπηρεσίας', 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateServiceRequest $request, Service $service)
    {
        try {
            $service->update($request->validated());

            return $this->successResponse($service, 'Η υπηρεσία ενημερώθηκε επιτυχώς');
        } catch (\Exception $e) {
            return $this->errorResponse('Σφάλμα κατά την ενημέρωση υπηρεσίας', 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Service $service)
    {
        try {
            // Έλεγχος αν η υπηρεσία έχει πακέτα
            if ($service->packages()->count() > 0) {
                return $this->errorResponse('Δεν μπορείτε να διαγράψετε αυτή την υπηρεσία γιατί έχει συνδεδεμένα πακέτα', 400);
            }

            // Έλεγχος αν η υπηρεσία έχει δοκιμαστικά ραντεβού
            if ($service->trialAppointments()->count() > 0) {
                return $this->errorResponse('Δεν μπορείτε να διαγράψετε αυτή την υπηρεσία γιατί έχει δοκιμαστικά ραντεβού', 400);
            }

            $service->delete();

            return $this->successResponse(null, 'Η υπηρεσία διαγράφηκε επιτυχώς');
        } catch (\Exception $e) {
            return $this->errorResponse('Σφάλμα κατά τη διαγραφή υπηρεσίας', 500);
        }
    }

    /**
     * Toggle active status of the service.
     */
    public function toggleActive(Service $service)
    {
        try {
            $service->update(['is_active' => !$service->is_active]);

            $message = $service->is_active ? 'Η υπηρεσία ενεργοποιήθηκε' : 'Η υπηρεσία απενεργοποιήθηκε';

            return $this->successResponse($service, $message);
        } catch (\Exception $e) {
            return $this->errorResponse('Σφάλμα κατά την ενημέρωση κατάστασης υπηρεσίας', 500);
        }
    }

    /**
     * Get trial information for a service.
     *
     * Επιστρέφει πληροφορίες για τα δοκιμαστικά ραντεβού μιας υπηρεσίας,
     * συμπεριλαμβανομένου αν ο χρήστης έχει ενεργή συνδρομή.
     *
     * Αν έχει ενεργή συνδρομή → επιτρέπονται απεριόριστα δοκιμαστικά
     * Αν δεν έχει ενεργή συνδρομή → ισχύει ο περιορισμός max_trial_per_user
     */
    public function getTrialInfo(Request $request, Service $service)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return $this->errorResponse('Χρειάζεται αυθεντικοποίηση', 401);
            }

            // Έλεγχος αν ο χρήστης έχει ενεργή συνδρομή για αυτή την υπηρεσία
            $hasActiveSubscription = $user->userPackages()
                ->whereHas('package', function($query) use ($service) {
                    $query->where('service_id', $service->id);
                })
                ->where('status', 'active')
                ->where('is_frozen', false)
                ->whereRaw('(expiry_date IS NULL OR expiry_date > ?)', [now()])
                ->where('remaining_sessions', '>', 0)
                ->exists();

            $trialInfo = [
                'service' => $service,
                'allows_trial' => $service->allows_trial,
                'trial_price' => $service->trial_price,
                'max_trial_per_user' => $service->max_trial_per_user,
                'user_trial_count' => $service->getTrialCountForUser($user),
                'can_book_trial' => $service->allowsTrialForUser($user),
                'has_active_subscription' => $hasActiveSubscription,
                'subscription_reason' => $hasActiveSubscription
                    ? 'Έχετε ενεργή συνδρομή για αυτή την υπηρεσία - επιτρέπονται απεριόριστα δοκιμαστικά'
                    : 'Δεν έχετε ενεργή συνδρομή - ισχύει ο περιορισμός δοκιμαστικών',
            ];

            return $this->successResponse($trialInfo, 'Πληροφορίες δοκιμαστικού ανακτήθηκαν επιτυχώς');
        } catch (\Exception $e) {
            return $this->errorResponse('Σφάλμα κατά την ανάκτηση πληροφοριών δοκιμαστικού', 500);
        }
    }
}
