<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request for validating bulk Twitch stats import
 */
class BulkImportStatsRequest extends FormRequest
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
            'stats' => 'present|array|min:0',
            'stats.*.userId' => 'required|string',
            'stats.*.userName' => 'nullable|string',
            'stats.*.platform' => 'nullable|string',
            'stats.*.name' => 'required|string',
            'stats.*.value' => 'required',
            'stats.*.lastWrite' => 'nullable|date',
        ];
    }

    /**
     * Get custom error messages for validation rules
     */
    public function messages(): array
    {
        return [
            'stats.present' => 'The stats field is required.',
            'stats.array' => 'The stats must be an array.',
            'stats.*.userId.required' => 'Each stat must have a userId.',
            'stats.*.userId.string' => 'The userId must be a string.',
            'stats.*.name.required' => 'Each stat must have a name.',
            'stats.*.name.string' => 'The stat name must be a string.',
            'stats.*.value.required' => 'Each stat must have a value.',
            'stats.*.lastWrite.date' => 'The lastWrite must be a valid date.',
        ];
    }
}
