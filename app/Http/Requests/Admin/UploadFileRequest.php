<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request for validating file uploads in the admin file sharing feature
 */
class UploadFileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * Get the validation rules that apply to the request
     */
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'max:5242880', // 5GB in KB
                'mimes:mp4,avi,mov,wmv,flv,webm,mkv,mp3,wav,ogg,flac,aac,m4a,jpg,jpeg,png,gif,webp,svg,pdf,doc,docx,xls,xlsx,ppt,pptx,txt,csv,zip,rar,7z',
            ],
        ];
    }

    /**
     * Get custom error messages for validation rules
     */
    public function messages(): array
    {
        return [
            'file.required' => 'Please select a file to upload.',
            'file.file' => 'The uploaded file is not valid.',
            'file.max' => 'The file size must not exceed 5GB.',
            'file.mimes' => 'The file type is not allowed. Allowed types: videos, images, documents, archives.',
        ];
    }
}
