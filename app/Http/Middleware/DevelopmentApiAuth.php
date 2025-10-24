<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class DevelopmentApiAuth
{
    /**
     * Handle an incoming request.
     * Supports both development API keys (from .env) and regular Sanctum tokens
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (! $token) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Check if it's a development API key from .env
        $devApiKey = config('app.dev_api_key');
        if ($devApiKey && $token === $devApiKey) {
            // For development, create a temporary user or use a system user
            $user = \App\Models\User::firstOrCreate(
                ['email' => 'dev@streamerbot.local'],
                [
                    'name' => 'Development API User',
                    'password' => bcrypt(uniqid()),
                ]
            );

            Auth::login($user);

            return $next($request);
        }

        // Check if it's a valid Sanctum token
        $accessToken = PersonalAccessToken::findToken($token);

        if (! $accessToken) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Check if token is expired
        if ($accessToken->expires_at && $accessToken->expires_at->isPast()) {
            return response()->json(['message' => 'Token expired.'], 401);
        }

        // Authenticate the user
        Auth::login($accessToken->tokenable);

        return $next($request);
    }
}
