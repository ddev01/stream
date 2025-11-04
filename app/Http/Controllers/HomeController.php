<?php

namespace App\Http\Controllers;

use App\Models\TwitchUserStat;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    public function index()
    {
        // Get top 5 for points
        $topPoints = TwitchUserStat::with('twitchUser')
            ->where('name', 'points')
            ->orderByRaw('CAST(value AS INTEGER) DESC')
            ->limit(5)
            ->get();

        // Get top 5 for watchtime
        $topWatchtime = TwitchUserStat::with('twitchUser')
            ->where('name', 'watchtime')
            ->orderByRaw('CAST(value AS INTEGER) DESC')
            ->limit(5)
            ->get();

        // Get top 5 for topThreeCount
        $topTopThree = TwitchUserStat::with('twitchUser')
            ->where('name', 'topThreeCount')
            ->orderByRaw('CAST(value AS INTEGER) DESC')
            ->limit(5)
            ->get();

        $userStats = null;
        $userPosition = null;
        $userWatchtimeStats = null;
        $userWatchtimePosition = null;
        $userTopThreeStats = null;
        $userTopThreePosition = null;

        // Get authenticated user's stats and positions if logged in
        if (Auth::check()) {
            $user = Auth::user();

            // Get the associated Twitch user
            $twitchUser = $user->twitchUser;

            if ($twitchUser) {
                // Get user's points stat
                $userPointsStat = TwitchUserStat::where('twitch_user_id', $twitchUser->id)
                    ->where('name', 'points')
                    ->first();

                if ($userPointsStat) {
                    $userStats = $userPointsStat;
                    $userPosition = TwitchUserStat::where('name', 'points')
                        ->whereRaw('CAST(value AS INTEGER) > ?', [(int) $userPointsStat->value])
                        ->count() + 1;
                }

                // Get user's watchtime stat
                $userWatchtimeStat = TwitchUserStat::where('twitch_user_id', $twitchUser->id)
                    ->where('name', 'watchtime')
                    ->first();

                if ($userWatchtimeStat) {
                    $userWatchtimeStats = $userWatchtimeStat;
                    $userWatchtimePosition = TwitchUserStat::where('name', 'watchtime')
                        ->whereRaw('CAST(value AS INTEGER) > ?', [(int) $userWatchtimeStat->value])
                        ->count() + 1;
                }

                // Get user's topThreeCount stat
                $userTopThreeStat = TwitchUserStat::where('twitch_user_id', $twitchUser->id)
                    ->where('name', 'topThreeCount')
                    ->first();

                if ($userTopThreeStat) {
                    $userTopThreeStats = $userTopThreeStat;
                    $userTopThreePosition = TwitchUserStat::where('name', 'topThreeCount')
                        ->whereRaw('CAST(value AS INTEGER) > ?', [(int) $userTopThreeStat->value])
                        ->count() + 1;
                }
            }
        }

        return view('home', [
            'topPoints' => $topPoints,
            'topWatchtime' => $topWatchtime,
            'topTopThree' => $topTopThree,
            'userStats' => $userStats,
            'userPosition' => $userPosition,
            'userWatchtimeStats' => $userWatchtimeStats,
            'userWatchtimePosition' => $userWatchtimePosition,
            'userTopThreeStats' => $userTopThreeStats,
            'userTopThreePosition' => $userTopThreePosition,
        ]);
    }
}
