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
        $topGifters = $leaderboardService->getTopGifters(5);
        $topTriviaWins = $leaderboardService->getTopStats('triviaWins');
        $topRaiders = $leaderboardService->getTopRaiders(5);
        $topRaiderViews = $leaderboardService->getTopRaiderViews(5);
        $recentRaidHistory = $leaderboardService->getRecentRaidHistory(5);

        // Get authenticated user's stats and positions if logged in
        $userPointsData = null;
        $userWatchtimeData = null;
        $userTopThreeData = null;
        $userGifterData = null;
        $userTriviaWinsData = null;
        $userRaiderData = null;
        $userRaiderViewsData = null;

        if (Auth::check()) {
            $user = Auth::user();
            $userPointsData = $leaderboardService->getUserStatAndPosition($user, 'points');
            $userWatchtimeData = $leaderboardService->getUserStatAndPosition($user, 'watchtime');
            $userTopThreeData = $leaderboardService->getUserStatAndPosition($user, 'topThreeCount');
            $userGifterData = $leaderboardService->getUserGifterStatAndPosition($user);
            $userTriviaWinsData = $leaderboardService->getUserStatAndPosition($user, 'triviaWins');
            $userRaiderData = $leaderboardService->getUserRaiderStatAndPosition($user);
            $userRaiderViewsData = $leaderboardService->getUserRaiderViewsStatAndPosition($user);
        }

        return view('home', [
            'topPoints' => $topPoints,
            'topWatchtime' => $topWatchtime,
            'topTopThree' => $topTopThree,
            'topGifters' => $topGifters,
            'topTriviaWins' => $topTriviaWins,
            'topRaiders' => $topRaiders,
            'topRaiderViews' => $topRaiderViews,
            'recentRaidHistory' => $recentRaidHistory,
            'userStats' => $userPointsData['stat'] ?? null,
            'userPosition' => $userPointsData['position'] ?? null,
            'userWatchtimeStats' => $userWatchtimeData['stat'] ?? null,
            'userWatchtimePosition' => $userWatchtimeData['position'] ?? null,
            'userTopThreeStats' => $userTopThreeData['stat'] ?? null,
            'userTopThreePosition' => $userTopThreeData['position'] ?? null,
            'userGifterStats' => $userGifterData['stat'] ?? null,
            'userGifterPosition' => $userGifterData['position'] ?? null,
            'userTriviaWinsStats' => $userTriviaWinsData['stat'] ?? null,
            'userTriviaWinsPosition' => $userTriviaWinsData['position'] ?? null,
            'userRaiderStats' => $userRaiderData['stat'] ?? null,
            'userRaiderPosition' => $userRaiderData['position'] ?? null,
            'userRaiderViewsStats' => $userRaiderViewsData['stat'] ?? null,
            'userRaiderViewsPosition' => $userRaiderViewsData['position'] ?? null,
        ]);
    }
}
