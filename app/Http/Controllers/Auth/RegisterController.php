<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

/**
 * Handles user registration redirects
 */
class RegisterController extends Controller
{
    /**
     * Redirect to login since we only use Twitch OAuth
     */
    public function redirect(): RedirectResponse
    {
        return redirect('/login');
    }
}
