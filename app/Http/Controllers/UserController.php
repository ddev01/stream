<?php

namespace App\Http\Controllers;

use App\Models\User;

/**
 * Controller for user-related pages
 */
class UserController extends Controller
{
    /**
     * Display the specified user's profile
     */
    public function show(User $user): \Illuminate\View\View
    {
        return view('users.show', ['user' => $user]);
    }
}
