<?php

namespace App\Http\Controllers;

use App\Services\LeaderboardService;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    /**
     * Display the home page with leaderboards
     */
    public function index(LeaderboardService $leaderboardService): \Illuminate\View\View
    {
        // Get top stats for each leaderboard
        $topPoints = $leaderboardService->getTopStats('points');
        $topWatchtime = $leaderboardService->getTopStats('watchtime');
        $topTopThree = $leaderboardService->getTopStats('topThreeCount');

        // Get authenticated user's stats and positions if logged in
        $userPointsData = null;
        $userWatchtimeData = null;
        $userTopThreeData = null;

        if (Auth::check()) {
            $user = Auth::user();
            $userPointsData = $leaderboardService->getUserStatAndPosition($user, 'points');
            $userWatchtimeData = $leaderboardService->getUserStatAndPosition($user, 'watchtime');
            $userTopThreeData = $leaderboardService->getUserStatAndPosition($user, 'topThreeCount');
        }

        return view('home', [
            'topPoints' => $topPoints,
            'topWatchtime' => $topWatchtime,
            'topTopThree' => $topTopThree,
            'userStats' => $userPointsData['stat'] ?? null,
            'userPosition' => $userPointsData['position'] ?? null,
            'userWatchtimeStats' => $userWatchtimeData['stat'] ?? null,
            'userWatchtimePosition' => $userWatchtimeData['position'] ?? null,
            'userTopThreeStats' => $userTopThreeData['stat'] ?? null,
            'userTopThreePosition' => $userTopThreeData['position'] ?? null,
        ]);
    }
}
