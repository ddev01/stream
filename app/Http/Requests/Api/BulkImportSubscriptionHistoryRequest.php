<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request for validating bulk subscription history import
 */
class BulkImportSubscriptionHistoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request
     */
    public function authorize(): bool
    {
        // Authorization is handled by middleware (dev.api)
        return true;
    }

    /**
     * Get the validation rules that apply to the request
     */
    public function rules(): array
    {
        return [
            'subscription_history' => 'present|array|min:0',
            'subscription_history.*._id' => 'required|array',
            'subscription_history.*._id.$oid' => 'required|string',
            'subscription_history.*.subscribedAt' => 'required|array',
            'subscription_history.*.subscribedAt.$date' => 'required|string',
            'subscription_history.*.userId' => 'required|string',
            'subscription_history.*.gifterUserId' => 'required|string',
        ];
    }

    /**
     * Get custom error messages for validation rules
     */
    public function messages(): array
    {
        return [
            'subscription_history.present' => 'The subscription_history field is required.',
            'subscription_history.array' => 'The subscription_history must be an array.',
            'subscription_history.*._id.required' => 'Each subscription history entry must have an _id field.',
            'subscription_history.*._id.array' => 'The _id field must be an object.',
            'subscription_history.*._id.$oid.required' => 'Each subscription history entry must have an _id.$oid field.',
            'subscription_history.*._id.$oid.string' => 'The _id.$oid must be a string.',
            'subscription_history.*.subscribedAt.required' => 'Each subscription history entry must have a subscribedAt field.',
            'subscription_history.*.subscribedAt.array' => 'The subscribedAt field must be an object.',
            'subscription_history.*.subscribedAt.$date.required' => 'Each subscription history entry must have a subscribedAt.$date field.',
            'subscription_history.*.subscribedAt.$date.string' => 'The subscribedAt.$date must be a string.',
            'subscription_history.*.userId.required' => 'Each subscription history entry must have a userId.',
            'subscription_history.*.userId.string' => 'The userId must be a string.',
            'subscription_history.*.gifterUserId.required' => 'Each subscription history entry must have a gifterUserId.',
            'subscription_history.*.gifterUserId.string' => 'The gifterUserId must be a string.',
        ];
    }
}
