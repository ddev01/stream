<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Represents a file that has been uploaded and shared via a shareable URL
 */
class SharedFile extends Model
{
    private const int ShareTokenLength = 64;

    protected $fillable = [
        'user_id',
        'original_filename',
        'stored_filename',
        'share_token',
        'mime_type',
        'file_size',
        'file_path',
    ];

    /**
     * Get the attributes that should be cast
     */
    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
        ];
    }

    /**
     * Use share tokens for route model binding.
     */
    public function getRouteKeyName(): string
    {
        return 'share_token';
    }

    /**
     * Get the user who uploaded this file
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Generate a unique share token
     */
    public static function generateShareToken(): string
    {
        do {
            $token = Str::random(self::ShareTokenLength);
        } while (self::where('share_token', $token)->exists());

        return $token;
    }

    /**
     * Get the full URL to access this shared file
     */
    public function getShareUrlAttribute(): string
    {
        return route('shared-files.show', $this);
    }

    /**
     * Get the token-protected URL to the file itself
     */
    public function getFileUrlAttribute(): string
    {
        return route('shared-files.file', $this);
    }

    /**
     * Get the token-protected URL to download the file
     */
    public function getDownloadUrlAttribute(): string
    {
        return route('shared-files.download', $this);
    }

    /**
     * Check if this file is a video
     */
    public function isVideo(): bool
    {
        return str_starts_with($this->mime_type, 'video/');
    }

    /**
     * Check if this file is an image
     */
    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    /**
     * Get human-readable file size
     */
    public function getHumanReadableSizeAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;

        while ($bytes >= 1024 && $i < \count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return \round($bytes, 2).' '.$units[$i];
    }
}
