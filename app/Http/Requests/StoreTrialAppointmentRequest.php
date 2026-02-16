<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTrialAppointmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'service_id' => 'required|exists:services,id',
            'appointment_date' => 'required|date|after_or_equal:today',
            'appointment_time' => 'required|date_format:H:i',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'service_id.required' => 'Η υπηρεσία είναι υποχρεωτική.',
            'service_id.exists' => 'Η επιλεγμένη υπηρεσία δεν υπάρχει.',
            'appointment_date.required' => 'Η ημερομηνία ραντεβού είναι υποχρεωτική.',
            'appointment_date.date' => 'Η ημερομηνία ραντεβού πρέπει να είναι έγκυρη.',
            'appointment_date.after_or_equal' => 'Η ημερομηνία ραντεβού δεν μπορεί να είναι στο παρελθόν.',
            'appointment_time.required' => 'Η ώρα ραντεβού είναι υποχρεωτική.',
            'appointment_time.date_format' => 'Η ώρα ραντεβού πρέπει να είναι σε μορφή HH:MM.',
            'notes.max' => 'Οι σημειώσεις δεν μπορούν να υπερβαίνουν τους 1000 χαρακτήρες.',
        ];
    }

    /**
     * Configure the validator instance.
     *
     * Ελέγχει την ικανότητα του χρήστη να κάνει δοκιμαστικό ραντεβού:
     * - Αν έχει ΕΝΕΡΓΗ συνδρομή για την υπηρεσία → επιτρέπεται
     * - Αν ΔΕΝ έχει ενεργή συνδρομή → έλεγχος max_trial_per_user
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $user = auth()->user();
            $serviceId = $this->input('service_id');

            if ($serviceId) {
                $service = \App\Models\Service::find($serviceId);

                if ($service) {
                    // Έλεγχος αν ο χρήστης μπορεί να κάνει δοκιμαστικό
                    if (!$service->allowsTrialForUser($user)) {
                        // Δημιουργία πιο συγκεκριμένου μηνύματος ανάλογα με το αν έχει ενεργή συνδρομή
                        $hasActiveSubscription = $user->userPackages()
                            ->whereHas('package', function($query) use ($serviceId) {
                                $query->where('service_id', $serviceId);
                            })
                            ->where('status', 'active')
                            ->where('is_frozen', false)
                            ->whereRaw('(expiry_date IS NULL OR expiry_date > ?)', [now()])
                            ->where('remaining_sessions', '>', 0)
                            ->exists();

                        if ($hasActiveSubscription) {
                            $validator->errors()->add('service_id', 'Έχετε ήδη ενεργή συνδρομή για αυτή την υπηρεσία.');
                        } else {
                            $validator->errors()->add('service_id', 'Έχετε ήδη κάνει το μέγιστο αριθμό δοκιμαστικών ραντεβού για αυτή την υπηρεσία.');
                        }
                    }

                    // Έλεγχος για διπλό ραντεβού την ίδια ημέρα/ώρα
                    $existingAppointment = \App\Models\TrialAppointment::where('user_id', $user->id)
                        ->where('appointment_date', $this->input('appointment_date'))
                        ->where('appointment_time', $this->input('appointment_time'))
                        ->where('status', '!=', 'cancelled')
                        ->first();

                    if ($existingAppointment) {
                        $validator->errors()->add('appointment_time', 'Έχετε ήδη ραντεβού αυτή την ημέρα και ώρα.');
                    }
                }
            }
        });
    }
}
