<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkPackageExtensionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->membership_type === 'Admin';
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // Filter rules
            'filters.status' => ['nullable', 'string', Rule::in(['active', 'paused', 'expired', 'expiring_soon', 'frozen'])],
            'filters.package_id' => ['nullable', 'integer', 'exists:packages,id'],
            'filters.user_ids' => ['nullable', 'array'],
            'filters.user_ids.*' => ['integer', 'exists:users,id'],
            'filters.user_search' => ['nullable', 'string', 'max:255'],
            'filters.expiry_from' => ['nullable', 'date'],
            'filters.expiry_to' => ['nullable', 'date', 'after_or_equal:filters.expiry_from'],
            'filters.min_sessions' => ['nullable', 'integer', 'min:0'],
            'filters.max_sessions' => ['nullable', 'integer', 'min:0', 'gte:filters.min_sessions'],
            'filters.auto_renew' => ['nullable', 'boolean'],
            
            // Extension rules
            'extension.extend_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'extension.extend_weeks' => ['nullable', 'integer', 'min:1', 'max:52'],
            'extension.extend_months' => ['nullable', 'integer', 'min:1', 'max:12'],
            'extension.set_expiry_date' => ['nullable', 'date', 'after:today'],
            'extension.add_sessions' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'extension.set_sessions' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'extension.discount_amount' => ['nullable', 'numeric', 'min:0'],
            'extension.discount_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            
            // Operation options
            'preview_only' => ['boolean'],
            'send_notifications' => ['boolean'],
            'confirmation_token' => ['required_if:preview_only,false', 'string'],
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'filters.status.in' => 'Invalid status filter. Must be one of: active, paused, expired, expiring_soon, frozen.',
            'filters.package_id.exists' => 'Selected package does not exist.',
            'filters.user_ids.*.exists' => 'One or more selected users do not exist.',
            'filters.expiry_to.after_or_equal' => 'Expiry end date must be after or equal to start date.',
            'filters.max_sessions.gte' => 'Maximum sessions must be greater than or equal to minimum sessions.',
            'extension.extend_days.max' => 'Cannot extend more than 365 days at once.',
            'extension.extend_weeks.max' => 'Cannot extend more than 52 weeks at once.',
            'extension.extend_months.max' => 'Cannot extend more than 12 months at once.',
            'extension.set_expiry_date.after' => 'New expiry date must be in the future.',
            'extension.add_sessions.max' => 'Cannot add more than 1000 sessions at once.',
            'extension.set_sessions.max' => 'Cannot set more than 1000 sessions.',
            'extension.discount_percentage.max' => 'Discount percentage cannot exceed 100%.',
            'confirmation_token.required_if' => 'Confirmation token is required for non-preview operations.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Ensure at least one extension option is provided
            $extension = $this->input('extension', []);
            $hasExtension = !empty($extension['extend_days']) || 
                          !empty($extension['extend_weeks']) || 
                          !empty($extension['extend_months']) || 
                          !empty($extension['set_expiry_date']) ||
                          !empty($extension['add_sessions']) ||
                          !empty($extension['set_sessions']) ||
                          !empty($extension['discount_amount']) ||
                          !empty($extension['discount_percentage']);
            
            if (!$hasExtension) {
                $validator->errors()->add('extension', 'At least one extension option must be provided.');
            }
            
            // Validate extension conflicts
            if (!empty($extension['extend_days']) && !empty($extension['set_expiry_date'])) {
                $validator->errors()->add('extension', 'Cannot use both extend days and set expiry date.');
            }
            
            if (!empty($extension['add_sessions']) && !empty($extension['set_sessions'])) {
                $validator->errors()->add('extension', 'Cannot use both add sessions and set sessions.');
            }
            
            if (!empty($extension['discount_amount']) && !empty($extension['discount_percentage'])) {
                $validator->errors()->add('extension', 'Cannot use both discount amount and discount percentage.');
            }
        });
    }

    /**
     * Get the validated filters.
     */
    public function getValidatedFilters(): array
    {
        return $this->validated()['filters'] ?? [];
    }

    /**
     * Get the validated extension data.
     */
    public function getValidatedExtension(): array
    {
        return $this->validated()['extension'] ?? [];
    }

    /**
     * Check if this is a preview-only request.
     */
    public function isPreviewOnly(): bool
    {
        return $this->boolean('preview_only', false);
    }

    /**
     * Check if notifications should be sent.
     */
    public function shouldSendNotifications(): bool
    {
        return $this->boolean('send_notifications', true);
    }

    /**
     * Get the confirmation token.
     */
    public function getConfirmationToken(): ?string
    {
        return $this->input('confirmation_token');
    }
}