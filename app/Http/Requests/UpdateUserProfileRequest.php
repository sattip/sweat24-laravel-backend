<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization is handled by middleware/controller logic
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $userId = $this->user() ? $this->user()->id : null;
        
        $rules = [
            'email' => 'sometimes|email|unique:users,email,' . $userId,
            'phone' => 'nullable|string|max:20|regex:/^([0-9\s\-\+\(\)]*)$/',
            'address' => 'nullable|string|max:500',
            'date_of_birth' => 'nullable|date|before:today|after:1900-01-01',
            'gender' => 'nullable|string|in:male,female,other,prefer_not_to_say',
            'weight' => 'nullable|numeric|between:30,300',
            'height' => 'nullable|numeric|between:100,250',
            'emergency_contact' => 'nullable|string|max:255',
            'emergency_phone' => 'nullable|string|max:20|regex:/^([0-9\s\-\+\(\)]*)$/',
            'medical_history' => 'nullable|string|max:2000',
            'notes' => 'nullable|string|max:1000',
        ];

        // Check if user can change name (only if not approved)
        $user = $this->user();
        if ($user && $user->canEditName()) {
            $rules['name'] = 'sometimes|string|max:255';
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Το όνομα είναι υποχρεωτικό.',
            'name.max' => 'Το όνομα δεν μπορεί να υπερβαίνει τους 255 χαρακτήρες.',
            'email.email' => 'Παρακαλώ εισάγετε μια έγκυρη διεύθυνση email.',
            'email.unique' => 'Αυτό το email χρησιμοποιείται ήδη.',
            'phone.regex' => 'Παρακαλώ εισάγετε έναν έγκυρο αριθμό τηλεφώνου.',
            'emergency_phone.regex' => 'Παρακαλώ εισάγετε έναν έγκυρο αριθμό τηλεφώνου έκτακτης ανάγκης.',
            'date_of_birth.before' => 'Η ημερομηνία γέννησης πρέπει να είναι στο παρελθόν.',
            'date_of_birth.after' => 'Παρακαλώ εισάγετε μια έγκυρη ημερομηνία γέννησης.',
            'gender.in' => 'Παρακαλώ επιλέξτε έγκυρο φύλο.',
            'weight.between' => 'Το βάρος πρέπει να είναι μεταξύ 30 και 300 kg.',
            'height.between' => 'Το ύψος πρέπει να είναι μεταξύ 100 και 250 cm.',
            'weight.numeric' => 'Το βάρος πρέπει να είναι αριθμός.',
            'height.numeric' => 'Το ύψος πρέπει να είναι αριθμός.',
            'medical_history.max' => 'Το ιατρικό ιστορικό δεν μπορεί να υπερβαίνει τους 2000 χαρακτήρες.',
            'notes.max' => 'Οι σημειώσεις δεν μπορούν να υπερβαίνουν τους 1000 χαρακτήρες.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */
    public function attributes(): array
    {
        return [
            'name' => 'όνομα',
            'email' => 'email',
            'phone' => 'τηλέφωνο',
            'address' => 'διεύθυνση',
            'date_of_birth' => 'ημερομηνία γέννησης',
            'gender' => 'φύλο',
            'weight' => 'βάρος',
            'height' => 'ύψος',
            'emergency_contact' => 'επαφή έκτακτης ανάγκης',
            'emergency_phone' => 'τηλέφωνο έκτακτης ανάγκης',
            'medical_history' => 'ιατρικό ιστορικό',
            'notes' => 'σημειώσεις',
        ];
    }
}
