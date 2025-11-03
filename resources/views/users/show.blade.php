<x-layouts.app :title="$user->name">
    <div class="space-y-6">
        <div class="flex items-center gap-4">
            @if($user->twitchUser?->profile_image_url)
                <img src="{{ $user->twitchUser->profile_image_url }}" alt="{{ $user->name }}" class="w-20 h-20 rounded-full">
            @else
                <div class="w-20 h-20 rounded-full bg-neutral-200 dark:bg-neutral-700 flex items-center justify-center text-2xl font-semibold">
                    {{ $user->initials() }}
                </div>
            @endif
            <div>
                <h1 class="text-2xl font-bold">{{ $user->name }}</h1>
                @if($user->twitchUser?->broadcaster_type)
                    <p class="text-sm text-neutral-600 dark:text-neutral-400">{{ ucfirst($user->twitchUser->broadcaster_type) }}</p>
                @endif
            </div>
        </div>

        @if($user->twitchUser?->description)
            <p class="text-neutral-700 dark:text-neutral-300">{{ $user->twitchUser->description }}</p>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            @php
                $points = $user->stat('points');
                $watchtime = $user->stat('watchtime');
                $topThreeCount = $user->stat('topThreeCount');
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
        </div>
    </div>
</x-layouts.app>

