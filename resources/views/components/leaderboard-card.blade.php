@props([
    'title',
    'topStats',
    'userStat' => null,
    'userPosition' => null,
    'valueFormatter' => null,
])

<div class="relative overflow-hidden rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
    <h2 class="mb-4 text-lg font-semibold">{{ $title }}</h2>
    <ol class="space-y-2">
        @foreach ($topStats as $stat)
            <li class="flex items-center justify-between gap-4">
                <span class="flex items-center gap-2">
                    <strong class="text-sm font-medium">{{ $loop->iteration }}.</strong>
                    <span class="text-sm">{{ $stat->twitchUser->display_name ?? $stat->twitch_user_id }}</span>
                </span>
                <span class="font-mono text-sm">
                    @if ($valueFormatter)
                        {{ $valueFormatter($stat->value) }}
                    @else
                        {{ number_format($stat->value) }}
                    @endif
                </span>
            </li>
        @endforeach
    </ol>

    @if ($userStat && $userPosition)
        <div class="mt-2 border-t border-neutral-200 pt-4 dark:border-neutral-700">
            <h3 class="text-sm font-medium text-neutral-600 dark:text-neutral-400">Your Stats</h3>
            <div class="flex items-center justify-between">
                <span class="text-sm">
                    <strong>Position:</strong> #{{ $userPosition }}
                </span>
                <span class="font-mono text-sm">
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
