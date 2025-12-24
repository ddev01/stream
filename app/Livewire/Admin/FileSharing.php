<?php

namespace App\Livewire\Admin;

use App\Models\SharedFile;
use App\Support\SharedFileExpiry;
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

    public string $expiryPreset = '3d';

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
     * Get the available expiry options.
     *
     * @return array<string, string>
     */
    public function getExpiryOptionsProperty(): array
    {
        return SharedFileExpiry::presetOptions();
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
        $uploadedFile = null;
        $storedPath = null;

        try {
            $validated = $this->validate();

            $uploadedFile = $validated['file'];

            // Store the file
            $path = $uploadedFile->store('shared-files', self::StorageDisk);
            $storedFilename = \basename($path);
            $storedPath = $path;

            // Calculate expires_at from preset
            $expiresAt = SharedFileExpiry::expiresAt($this->expiryPreset);

            // Create database record
            $sharedFile = SharedFile::create([
                'user_id' => auth()->id(),
                'original_filename' => $uploadedFile->getClientOriginalName(),
                'stored_filename' => $storedFilename,
                'share_token' => SharedFile::generateShareToken(),
                'mime_type' => $uploadedFile->getMimeType(),
                'file_size' => $uploadedFile->getSize(),
                'file_path' => $path,
                'expires_at' => $expiresAt,
            ]);

            // Generate share URL
            $this->shareUrl = $sharedFile->share_url;

            // Reset file
            $this->file = null;
        } catch (ValidationException $e) {
            $this->cleanupStoredFile($storedPath);

            throw $e;
        } catch (\Exception $e) {
            $this->cleanupStoredFile($storedPath);

            report($e);
            session()->flash('error', 'Failed to upload file. Please try again.');
        } finally {
            $this->cleanupTemporaryUploadedFile($uploadedFile);
        }
    }

    /**
     * Delete a stored file on disk if it exists.
     */
    private function cleanupStoredFile(?string $storedPath): void
    {
        if (! $storedPath) {
            return;
        }

        $disk = \Illuminate\Support\Facades\Storage::disk(self::StorageDisk);

        if (! $disk->exists($storedPath)) {
            return;
        }

        try {
            $disk->delete($storedPath);
        } catch (\Throwable) {
            // Best-effort cleanup only.
        }
    }

    /**
     * Delete a Livewire temporary uploaded file.
     */
    private function cleanupTemporaryUploadedFile(?TemporaryUploadedFile $uploadedFile): void
    {
        if (! $uploadedFile) {
            return;
        }

        try {
            $uploadedFile->delete();
        } catch (\Throwable) {
            // Best-effort cleanup only.
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
