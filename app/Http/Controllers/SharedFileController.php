<?php

namespace App\Http\Controllers;

use App\Models\SharedFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Handles public viewing of shared files
 */
class SharedFileController extends Controller
{
    /**
     * Display a shared file based on its token
     */
    public function show(string $token): \Illuminate\View\View|\Illuminate\Http\RedirectResponse
    {
        $sharedFile = SharedFile::where('share_token', $token)->firstOrFail();

        // Check if file exists
        if (! Storage::disk('public')->exists($sharedFile->file_path)) {
            abort(404, 'File not found');
        }

        return view('shared-files.show', [
            'sharedFile' => $sharedFile,
        ]);
    }

    /**
     * Stream the shared file content (used by <img> and <video> sources).
     */
    public function file(Request $request, string $token): \Symfony\Component\HttpFoundation\Response
    {
        $sharedFile = SharedFile::where('share_token', $token)->firstOrFail();

        if (! Storage::disk('public')->exists($sharedFile->file_path)) {
            abort(404, 'File not found');
        }

        $disk = Storage::disk('public');
        $absolutePath = $disk->path($sharedFile->file_path);
        $size = $disk->size($sharedFile->file_path);

        $baseHeaders = [
            'Content-Type' => $sharedFile->mime_type,
            'Content-Disposition' => 'inline; filename="'.$sharedFile->original_filename.'"',
            'Accept-Ranges' => 'bytes',
        ];

        $range = $request->header('Range');
        if (! $range) {
            return response()->file($absolutePath, array_merge($baseHeaders, [
                'Content-Length' => (string) $size,
            ]));
        }

        if (! preg_match('/bytes=(\d+)-(\d*)/i', $range, $matches)) {
            return response()->file($absolutePath, array_merge($baseHeaders, [
                'Content-Length' => (string) $size,
            ]));
        }

        $start = (int) $matches[1];
        $end = $matches[2] !== '' ? (int) $matches[2] : ($size - 1);

        $start = max(0, $start);
        $end = min($size - 1, $end);

        if ($start > $end) {
            abort(416, 'Requested Range Not Satisfiable');
        }

        $length = ($end - $start) + 1;

        $stream = function () use ($absolutePath, $start, $length): void {
            $handle = fopen($absolutePath, 'rb');
            if ($handle === false) {
                return;
            }

            fseek($handle, $start);

            $remaining = $length;
            while ($remaining > 0 && ! feof($handle)) {
                $chunkSize = min(1024 * 1024, $remaining);
                $buffer = fread($handle, $chunkSize);
                if ($buffer === false) {
                    break;
                }

                echo $buffer;
                flush();

                $remaining -= strlen($buffer);
            }

            fclose($handle);
        };

        return response()->stream($stream, 206, array_merge($baseHeaders, [
            'Content-Length' => (string) $length,
            'Content-Range' => "bytes {$start}-{$end}/{$size}",
        ]));
    }

    /**
     * Download the shared file.
     */
    public function download(string $token): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $sharedFile = SharedFile::where('share_token', $token)->firstOrFail();

        if (! Storage::disk('public')->exists($sharedFile->file_path)) {
            abort(404, 'File not found');
        }

        return Storage::disk('public')->download($sharedFile->file_path, $sharedFile->original_filename);
    }
}
