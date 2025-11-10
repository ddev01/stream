<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request for validating bulk raid history import
 */
class BulkImportRaidHistoryRequest extends FormRequest
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
            'raid_history' => 'present|array|min:0',
            'raid_history.*._id' => 'required|array',
            'raid_history.*._id.$oid' => 'required|string',
            'raid_history.*.timestamp' => 'required|array',
            'raid_history.*.timestamp.$date' => 'required|string',
            'raid_history.*.userId' => 'required|string',
            'raid_history.*.viewers' => 'required|integer|min:0',
        ];
    }

    /**
     * Get custom error messages for validation rules
     */
    public function messages(): array
    {
        return [
            'raid_history.present' => 'The raid_history field is required.',
            'raid_history.array' => 'The raid_history must be an array.',
            'raid_history.*._id.required' => 'Each raid history entry must have an _id field.',
            'raid_history.*._id.array' => 'The _id field must be an object.',
            'raid_history.*._id.$oid.required' => 'Each raid history entry must have an _id.$oid field.',
            'raid_history.*._id.$oid.string' => 'The _id.$oid must be a string.',
            'raid_history.*.timestamp.required' => 'Each raid history entry must have a timestamp field.',
            'raid_history.*.timestamp.array' => 'The timestamp field must be an object.',
            'raid_history.*.timestamp.$date.required' => 'Each raid history entry must have a timestamp.$date field.',
            'raid_history.*.timestamp.$date.string' => 'The timestamp.$date must be a string.',
            'raid_history.*.userId.required' => 'Each raid history entry must have a userId.',
            'raid_history.*.userId.string' => 'The userId must be a string.',
            'raid_history.*.viewers.required' => 'Each raid history entry must have a viewers count.',
            'raid_history.*.viewers.integer' => 'The viewers must be an integer.',
            'raid_history.*.viewers.min' => 'The viewers must be at least 0.',
        ];
    }
}
