<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreServiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:services,name',
            'slug' => 'nullable|string|max:255|unique:services,slug',
            'description' => 'nullable|string|max:1000',
            'icon' => 'nullable|string|max:255',
            'trial_price' => 'nullable|numeric|min:0|max:999999.99',
            'is_active' => 'boolean',
            'display_order' => 'integer|min:0|max:999',
            'allows_trial' => 'boolean',
            'max_trial_per_user' => 'integer|min:0|max:10',
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
            'name.required' => 'Το όνομα της υπηρεσίας είναι υποχρεωτικό.',
            'name.unique' => 'Υπάρχει ήδη υπηρεσία με αυτό το όνομα.',
            'slug.unique' => 'Υπάρχει ήδη υπηρεσία με αυτό το slug.',
            'trial_price.numeric' => 'Η τιμή δοκιμαστικού πρέπει να είναι αριθμός.',
            'trial_price.min' => 'Η τιμή δοκιμαστικού δεν μπορεί να είναι αρνητική.',
            'display_order.integer' => 'Η σειρά εμφάνισης πρέπει να είναι ακέραιος αριθμός.',
            'max_trial_per_user.integer' => 'Το μέγιστο αριθμός δοκιμαστικών πρέπει να είναι ακέραιος.',
            'max_trial_per_user.max' => 'Το μέγιστο αριθμός δοκιμαστικών δεν μπορεί να υπερβαίνει τα 10.',
        ];
    }
}
