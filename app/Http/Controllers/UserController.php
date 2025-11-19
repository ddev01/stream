<?php

namespace App\Http\Controllers;

use App\Models\TwitchUser;

/**
 * Controller for user-related pages
 */
class UserController extends Controller
{
    /**
     * Display the specified user's profile
     */
    public function show(TwitchUser $twitchUser): \Illuminate\View\View
    {
        return view('users.show', ['twitchUser' => $twitchUser]);
    }
}
