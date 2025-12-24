<?php

namespace App\Http\Controllers;

use App\Models\SharedFile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Handles public viewing of shared files
 */
class SharedFileController extends Controller
{
    private const string StorageDisk = 'local';

    /**
     * Ensure a shared file is accessible (not expired and present on disk).
     */
    private function ensureAccessible(SharedFile $sharedFile): void
    {
        if ($sharedFile->isExpired()) {
            abort(410, 'File has expired');
        }

        if (! Storage::disk(self::StorageDisk)->exists($sharedFile->file_path)) {
            abort(404, 'File not found');
        }
    }

    /**
     * Display a shared file based on its token
     */
    public function show(SharedFile $sharedFile): View|RedirectResponse
    {
        $this->ensureAccessible($sharedFile);

        return view('shared-files.show', [
            'sharedFile' => $sharedFile,
        ]);
    }

    /**
     * Stream the shared file content (used by <img> and <video> sources).
     */
    public function file(Request $request, SharedFile $sharedFile): \Symfony\Component\HttpFoundation\Response
    {
        $this->ensureAccessible($sharedFile);

        $disk = Storage::disk(self::StorageDisk);

        $absolutePath = $disk->path($sharedFile->file_path);
        $size = $disk->size($sharedFile->file_path);

        $disposition = (new ResponseHeaderBag)->makeDisposition(
            ResponseHeaderBag::DISPOSITION_INLINE,
            $sharedFile->original_filename,
            Str::ascii($sharedFile->original_filename)
        );

        $baseHeaders = [
            'Content-Type' => $sharedFile->mime_type,
            'Content-Disposition' => $disposition,
            'Accept-Ranges' => 'bytes',
            'X-Content-Type-Options' => 'nosniff',
        ];

        $range = $request->header('Range');
        if (! $range) {
            return response()->file($absolutePath, \array_merge($baseHeaders, [
                'Content-Length' => (string) $size,
            ]));
        }

        $matches = [];
        if (! \preg_match('/bytes=(\d+)-(\d*)/i', $range, $matches)) {
            return response()->file($absolutePath, \array_merge($baseHeaders, [
                'Content-Length' => (string) $size,
            ]));
        }

        $start = (int) $matches[1];
        $end = $matches[2] !== '' ? (int) $matches[2] : ($size - 1);

        $start = \max(0, $start);
        $end = \min($size - 1, $end);

        if ($start > $end) {
            abort(416, 'Requested Range Not Satisfiable');
        }

        $length = ($end - $start) + 1;

        $stream = function () use ($absolutePath, $start, $length): void {
            $handle = \fopen($absolutePath, 'rb');
            if ($handle === false) {
                return;
            }

            \fseek($handle, $start);

            $remaining = $length;
            while ($remaining > 0 && ! \feof($handle)) {
                $chunkSize = \min(1024 * 1024, $remaining);
                $buffer = \fread($handle, $chunkSize);
                if ($buffer === false) {
                    break;
                }

                echo $buffer;
                \flush();

                $remaining -= \strlen($buffer);
            }

            \fclose($handle);
        };

        return response()->stream($stream, 206, \array_merge($baseHeaders, [
            'Content-Length' => (string) $length,
            'Content-Range' => "bytes {$start}-{$end}/{$size}",
        ]));
    }

    /**
     * Download the shared file.
     */
    public function download(SharedFile $sharedFile): StreamedResponse
    {
        $this->ensureAccessible($sharedFile);

        return Storage::disk(self::StorageDisk)->download($sharedFile->file_path, $sharedFile->original_filename);
    }
}
