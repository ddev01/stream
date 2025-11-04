<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\TwitchAuthService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;

/**
 * Handles Twitch OAuth authentication flow
 */
class TwitchAuthController extends Controller
{
    /**
     * Create a new controller instance
     */
    public function __construct(
        private TwitchAuthService $authService
    ) {}

    /**
     * Redirect to Twitch OAuth provider
     */
    public function redirect(): SymfonyRedirectResponse
    {
        return Socialite::driver('twitch')->redirect();
    }

    /**
     * Handle Twitch OAuth callback
     */
    public function callback(): RedirectResponse
    {
        try {
            $twitchOAuthUser = Socialite::driver('twitch')->user();

            $user = $this->authService->handleOAuthCallback($twitchOAuthUser);

            Auth::login($user);

            return redirect('/');

        } catch (Exception $e) {
            logger()->error('Twitch OAuth callback failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect('/login')->with('error', 'Authentication failed. Please try again.');
        }
    }
}
