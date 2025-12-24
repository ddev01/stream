<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dev;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Proxy Vite development assets for local containerized environments.
 */
final class ViteAssetsController
{
    /**
     * Proxy a Vite dev server asset request.
     */
    public function __invoke(Request $request, string $path): Response
    {
        $path = ltrim($path, '/');

        if (
            $path === '' ||
            Str::contains($path, ['..', '://', '\\']) ||
            Str::startsWith($path, ['/'])
        ) {
            return response('Vite asset not found', 404);
        }

        $baseUrl = rtrim((string) config('vite.asset_proxy_url'), '/');

        if ($baseUrl === '') {
            return response('Vite asset not found', 404);
        }

        $url = "{$baseUrl}/{$path}";

        try {
            $proxyResponse = Http::timeout((int) config('vite.asset_proxy_timeout', 10))
                ->withHeaders([
                    'Accept' => $request->header('Accept', '*/*'),
                ])
                ->get($url);
        } catch (\Throwable) {
            return response('Vite asset not found', 404);
        }

        $headers = [
            'Content-Type' => $proxyResponse->header('Content-Type', 'application/octet-stream'),
            'Cache-Control' => 'no-cache',
        ];

        return response($proxyResponse->body(), $proxyResponse->status())->withHeaders($headers);
    }
}
