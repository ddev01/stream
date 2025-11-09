<?php

namespace App\Http\Controllers;

/**
 * Controller for leaderboard pages
 */
class LeaderboardController extends Controller
{
    /**
     * Display the points leaderboard
     */
    public function points(): \Illuminate\View\View
    {
        return view('points-leaderboard');
    }

    /**
     * Display the watchtime leaderboard
     */
    public function watchtime(): \Illuminate\View\View
    {
        return view('watchtime-leaderboard');
    }

    /**
     * Display the top three count leaderboard
     */
    public function topThree(): \Illuminate\View\View
    {
        return view('top-three-leaderboard');
    }

    /**
     * Display the gifted subscriptions leaderboard
     */
    public function giftedSubscriptions(): \Illuminate\View\View
    {
        return view('gifted-subscriptions-leaderboard');
    }
}
