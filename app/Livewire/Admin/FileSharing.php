<?php

namespace App\Livewire\Admin;

use App\Models\SharedFile;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * Admin file sharing component for uploading and sharing files
 */
class FileSharing extends Component
{
    use WithFileUploads;

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
     * Handle file upload
     */
    public function upload(): void
    {
        if (! $this->file) {
            return;
        }

        $this->shareUrl = null;

        try {
            // Validate the file
            $validated = $this->validate([
                'file' => [
                    'required',
                    'file',
                    'max:5242880', // 5GB in KB
                    'mimes:mp4,avi,mov,wmv,flv,webm,mkv,mp3,wav,ogg,flac,aac,m4a,jpg,jpeg,png,gif,webp,svg,pdf,doc,docx,xls,xlsx,ppt,pptx,txt,csv,zip,rar,7z',
                ],
            ]);

            $uploadedFile = $validated['file'];

            // Generate unique filename
            $extension = $uploadedFile->getClientOriginalExtension();
            $storedFilename = Str::random(40).'.'.$extension;

            // Store the file
            $path = $uploadedFile->storeAs('shared-files', $storedFilename, 'public');

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

            // Reset file
            $this->file = null;
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to upload file: '.$e->getMessage());
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
