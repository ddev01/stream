<?php

namespace App\Livewire\Admin;

use App\Models\SharedFile;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * Admin file sharing component for uploading and sharing files
 */
class FileSharing extends Component
{
    use WithFileUploads;

    private const string StorageDisk = 'local';

    private const string AllowedMimes = 'mp4,avi,mov,wmv,flv,webm,mkv,mp3,wav,ogg,flac,aac,m4a,jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,ppt,pptx,txt,csv,zip,rar,7z';

    public ?TemporaryUploadedFile $file = null;

    public ?string $shareUrl = null;

    /**
     * Mount the component and check admin access
     */
    public function mount(): void
    {
        if (! auth()->user()?->isAdmin()) {
            abort(403, 'Unauthorized');
        }
    }

    /**
     * Automatically start processing once a file is selected/uploaded to temp storage.
     */
    public function updatedFile(): void
    {
        $this->upload();
    }

    /**
     * Get the validation rules for file uploads.
     */
    protected function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'max:5242880', // 5GB in KB
                'mimes:'.self::AllowedMimes,
            ],
        ];
    }

    /**
     * Get custom validation error messages for file uploads.
     */
    protected function messages(): array
    {
        return [
            'file.required' => 'Please select a file to upload.',
            'file.file' => 'The uploaded file is not valid.',
            'file.max' => 'The file size must not exceed 5GB.',
            'file.mimes' => 'The file type is not allowed. Allowed types: videos, images, documents, archives.',
        ];
    }

    /**
     * Handle file upload
     */
    public function upload(): void
    {
        if (! $this->file) {
            return;
        }

        $this->shareUrl = null;

        try {
            $validated = $this->validate();

            $uploadedFile = $validated['file'];

            // Store the file
            $path = $uploadedFile->store('shared-files', self::StorageDisk);
            $storedFilename = \basename($path);

            // Create database record
            $sharedFile = SharedFile::create([
                'user_id' => auth()->id(),
                'original_filename' => $uploadedFile->getClientOriginalName(),
                'stored_filename' => $storedFilename,
                'share_token' => SharedFile::generateShareToken(),
                'mime_type' => $uploadedFile->getMimeType(),
                'file_size' => $uploadedFile->getSize(),
                'file_path' => $path,
            ]);

            // Generate share URL
            $this->shareUrl = $sharedFile->share_url;

            // Clean up temporary file to prevent double storage
            $uploadedFile->delete();

            // Reset file
            $this->file = null;
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            report($e);
            session()->flash('error', 'Failed to upload file. Please try again.');
        }
    }

    /**
     * Reset the form
     */
    public function resetForm(): void
    {
        $this->file = null;
        $this->shareUrl = null;
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.admin.file-sharing');
    }
}
