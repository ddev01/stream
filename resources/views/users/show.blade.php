<x-layouts.app :title="$twitchUser->display_name">
    <div class="space-y-6">
        <div class="flex items-center gap-4">
            @if($twitchUser->profile_image_url)
                <img src="{{ $twitchUser->profile_image_url }}" alt="{{ $twitchUser->display_name }}" class="w-20 h-20 rounded-full">
            @else
                <div class="w-20 h-20 rounded-full bg-neutral-200 dark:bg-neutral-700 flex items-center justify-center text-2xl font-semibold">
                    {{ $twitchUser->initials() }}
                </div>
            @endif
            <div>
                <h1 class="text-2xl font-bold">{{ $twitchUser->display_name }}</h1>
                @if($twitchUser->broadcaster_type)
                    <p class="text-sm text-neutral-600 dark:text-neutral-400">{{ ucfirst($twitchUser->broadcaster_type) }}</p>
                @endif
            </div>
        </div>

        @if($twitchUser->description)
            <p class="text-neutral-700 dark:text-neutral-300">{{ $twitchUser->description }}</p>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            @php
                $points = $twitchUser->getStat('points');
                $watchtime = $twitchUser->getStat('watchtime');
                $topThreeCount = $twitchUser->getStat('topThreeCount');
                $triviaWins = $twitchUser->getStat('triviaWins');
                $giftedSubs = $twitchUser->getGiftedSubsCount();
                $raids = $twitchUser->getRaidsCount();
            @endphp

            @if($points !== null)
                <div class="p-4 rounded-xl border border-neutral-200 dark:border-neutral-700">
                    <div class="text-sm text-neutral-600 dark:text-neutral-400">Points</div>
                    <div class="text-2xl font-bold">{{ number_format((int) $points) }}</div>
                </div>
            @endif

            @if($watchtime !== null)
                <div class="p-4 rounded-xl border border-neutral-200 dark:border-neutral-700">
                    <div class="text-sm text-neutral-600 dark:text-neutral-400">Watchtime</div>
                    <div class="text-2xl font-bold">
                        @php
                            $hours = floor((int) $watchtime / 3600);
                            $days = floor($hours / 24);
                            $hours = $hours % 24;
                        @endphp
                        @if($days > 0)
                            {{ $days }}d {{ $hours }}h
                        @else
                            {{ $hours }}h
                        @endif
                    </div>
                </div>
            @endif

            @if($topThreeCount !== null)
                <div class="p-4 rounded-xl border border-neutral-200 dark:border-neutral-700">
                    <div class="text-sm text-neutral-600 dark:text-neutral-400">Top 3 Count</div>
                    <div class="text-2xl font-bold">{{ number_format((int) $topThreeCount) }}</div>
                </div>
            @endif

            @if($triviaWins !== null)
                <div class="p-4 rounded-xl border border-neutral-200 dark:border-neutral-700">
                    <div class="text-sm text-neutral-600 dark:text-neutral-400">Trivia Wins</div>
                    <div class="text-2xl font-bold">{{ number_format((int) $triviaWins) }}</div>
                </div>
            @endif

            @if($giftedSubs > 0)
                <div class="p-4 rounded-xl border border-neutral-200 dark:border-neutral-700">
                    <div class="text-sm text-neutral-600 dark:text-neutral-400">Gifted Subs</div>
                    <div class="text-2xl font-bold">{{ number_format($giftedSubs) }}</div>
                </div>
            @endif

            @if($raids > 0)
                <div class="p-4 rounded-xl border border-neutral-200 dark:border-neutral-700">
                    <div class="text-sm text-neutral-600 dark:text-neutral-400">Raids</div>
                    <div class="text-2xl font-bold">{{ number_format($raids) }}</div>
                </div>
            @endif
        </div>
    </div>
</x-layouts.app>

