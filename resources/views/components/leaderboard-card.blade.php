@props([
    'title',
    'topStats',
    'userStat' => null,
    'userPosition' => null,
    'valueFormatter' => null,
])

@php
    $isRaiderCard = str_contains($title, 'Raider');
    $isRaiderViewsCard = str_contains($title, 'Raider Views');
    
    // Map titles to leaderboard routes
    $leaderboardRoutes = [
        'Top 5 Points' => 'leaderboards.points',
        'Top 5 Watchtime' => 'leaderboards.watchtime',
        'Top 5 Top Three' => 'leaderboards.top-three',
        'Top 5 Gifted Subs' => 'leaderboards.gifted-subscriptions',
        'Top 5 Trivia Wins' => 'leaderboards.trivia-wins',
        'Top 5 Raiders' => 'leaderboards.raiders',
        'Top 5 Raider Views' => 'leaderboards.raiders',
    ];
    $leaderboardUrl = $leaderboardRoutes[$title] ?? null;
@endphp

<div class="relative overflow-hidden rounded-xl border border-neutral-200 bg-white p-5 dark:border-neutral-800 dark:bg-neutral-900">
    <div class="mb-5 flex items-center justify-between">
        <h2 class="text-base font-semibold tracking-tight text-neutral-900 dark:text-neutral-100">{{ $title }}</h2>
        @if ($leaderboardUrl)
            <a href="{{ route($leaderboardUrl) }}" wire:navigate class="text-neutral-400 transition-colors hover:text-neutral-600 dark:text-neutral-500 dark:hover:text-neutral-300">
                <x-heroicon-o-arrow-top-right-on-square class="h-4 w-4" />
            </a>
        @endif
    </div>
    @if ($topStats->isEmpty())
        <p class="text-sm font-medium text-neutral-500 dark:text-neutral-400">No data available</p>
    @else
        <ol class="space-y-3">
            @foreach ($topStats as $stat)
                @php
                    $username = $stat->twitchUser->display_name ?? $stat->twitch_user_id;
                    $twitchUrl = $isRaiderCard ? 'https://www.twitch.tv/'.strtolower($username) : null;
                    // Use position calculated from full database (sequential, no ties)
                    $position = $stat->position ?? $loop->iteration;
                @endphp
                <li class="group flex items-center justify-between border-b border-neutral-100 pb-3 last:border-0 last:pb-0 dark:border-neutral-800">
                    <span class="flex items-center gap-3">
                        <span class="text-xs font-semibold tabular-nums text-neutral-400 dark:text-neutral-500">{{ $position }}.</span>
                        @if ($isRaiderCard && $twitchUrl)
                            <a href="{{ $twitchUrl }}" target="_blank" rel="noopener noreferrer" class="flex items-center gap-1.5 text-sm font-medium text-neutral-700 transition-colors hover:text-neutral-900 dark:text-neutral-300 dark:hover:text-neutral-100">
                                <span>{{ $username }}</span>
                                <x-heroicon-o-arrow-top-right-on-square class="h-3.5 w-3.5 shrink-0 opacity-60 transition-opacity group-hover:opacity-100" />
                            </a>
                        @else
                            <span class="text-sm font-medium text-neutral-700 dark:text-neutral-300">{{ $username }}</span>
                        @endif
                    </span>
                    <span class="flex items-center gap-1.5 text-xs font-mono tabular-nums text-neutral-600 dark:text-neutral-400">
                        @if ($valueFormatter)
                            {{ $valueFormatter($stat->value) }}
                        @else
                            {{ number_format($stat->value) }}
                        @endif
                        @if ($isRaiderViewsCard)
                            <x-heroicon-o-eye class="h-3.5 w-3.5 shrink-0 text-neutral-400 dark:text-neutral-500" />
                        @endif
                    </span>
                </li>
            @endforeach
        </ol>
    @endif

    @if ($userStat && $userPosition)
        <div class="mt-5 border-t border-neutral-200 pt-4 dark:border-neutral-800">
            <h3 class="mb-3 text-xs font-semibold uppercase tracking-wider text-neutral-500 dark:text-neutral-400">Your Stats</h3>
            <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-neutral-700 dark:text-neutral-300">
                    Position <span class="font-mono tabular-nums text-neutral-600 dark:text-neutral-400">#{{ $userPosition }}</span>
                </span>
                <span class="text-xs font-mono tabular-nums text-neutral-600 dark:text-neutral-400">
                    @if ($valueFormatter)
                        {{ $valueFormatter($userStat->value) }}
                    @else
                        {{ number_format($userStat->value) }}
                    @endif
                </span>
            </div>
        </div>
    @endif
</div>
