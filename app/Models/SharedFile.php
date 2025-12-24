<?php

namespace App\Models;

use Carbon\CarbonInterval;
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
        'expires_at',
    ];

    /**
     * Get the attributes that should be cast
     */
    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'expires_at' => 'datetime',
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

    /**
     * Check if this file has expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /**
     * Get human-readable time remaining label
     */
    public function getTimeRemainingLabelAttribute(): string
    {
        if ($this->expires_at === null) {
            return 'Permanent';
        }

        if ($this->isExpired()) {
            return 'Expired';
        }

        return $this->expires_at->diffForHumans();
    }

    /**
     * Get a compact time remaining label for admin tables (e.g. "2d 23h").
     */
    public function getTimeRemainingShortLabelAttribute(): string
    {
        if ($this->expires_at === null) {
            return 'Permanent';
        }

        if ($this->isExpired()) {
            return 'Expired';
        }

        $seconds = now('UTC')->diffInSeconds($this->expires_at, false);
        if ($seconds <= 0) {
            return 'Expired';
        }

        $interval = CarbonInterval::seconds($seconds)->cascade();

        $label = $interval->forHumans([
            'short' => true,
            'parts' => 2,
            'join' => true,
        ]);

        return \preg_replace('/\s+and\s+/i', ' ', $label) ?? $label;
    }
}
