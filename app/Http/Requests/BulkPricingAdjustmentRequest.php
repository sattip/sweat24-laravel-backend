<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkPricingAdjustmentRequest extends FormRequest
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
            
            // Pricing rules
            'pricing.discount_amount' => ['nullable', 'numeric', 'min:0'],
            'pricing.discount_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'pricing.adjustment_reason' => ['required', 'string', 'max:500'],
            'pricing.apply_to_renewals' => ['boolean'],
            
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
            'pricing.discount_percentage.max' => 'Discount percentage cannot exceed 100%.',
            'pricing.adjustment_reason.required' => 'Adjustment reason is required.',
            'pricing.adjustment_reason.max' => 'Adjustment reason cannot exceed 500 characters.',
            'confirmation_token.required_if' => 'Confirmation token is required for non-preview operations.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Ensure at least one pricing option is provided
            $pricing = $this->input('pricing', []);
            $hasPricing = !empty($pricing['discount_amount']) || 
                         !empty($pricing['discount_percentage']);
            
            if (!$hasPricing) {
                $validator->errors()->add('pricing', 'At least one pricing adjustment must be provided.');
            }
            
            // Validate pricing conflicts
            if (!empty($pricing['discount_amount']) && !empty($pricing['discount_percentage'])) {
                $validator->errors()->add('pricing', 'Cannot use both discount amount and discount percentage.');
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
     * Get the validated pricing data.
     */
    public function getValidatedPricing(): array
    {
        return $this->validated()['pricing'] ?? [];
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