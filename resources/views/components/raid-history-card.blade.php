@props(['title', 'recentRaids'])

@php
	use App\Support\TimeFormatter;
@endphp

<div class="relative overflow-hidden rounded-xl border border-neutral-200 bg-white p-4 lg:p-5 dark:border-neutral-800 dark:bg-neutral-900">
	<div class="mb-4 flex items-center justify-between lg:mb-5">
		<h2 class="text-base font-semibold tracking-tight text-neutral-900 dark:text-neutral-100">{{ $title }}</h2>
		<a class="text-neutral-400 transition-colors hover:text-neutral-600 dark:text-neutral-500 dark:hover:text-neutral-300" href="{{ route('leaderboards.raiders') }}" wire:navigate>
			<x-heroicon-o-arrow-top-right-on-square class="h-4 w-4" />
		</a>
	</div>
	@if ($recentRaids->isEmpty())
		<p class="text-sm font-medium text-neutral-500 dark:text-neutral-400">No recent raids</p>
	@else
		<ol class="space-y-3">
			@foreach ($recentRaids as $raid)
				@php
					$username = $raid->user->display_name ?? $raid->user_id;
					$twitchUrl = 'https://www.twitch.tv/' . strtolower($username);
				@endphp
				<li class="group flex items-center justify-between gap-2 border-b border-neutral-100 pb-3 last:border-0 last:pb-0 dark:border-neutral-800">
					<span class="flex min-w-0 flex-1 items-center gap-2 lg:gap-3">
						<span class="shrink-0 text-xs font-semibold tabular-nums text-neutral-400 dark:text-neutral-500">{{ $loop->iteration }}.</span>
						<a class="flex min-w-0 items-center gap-1.5 text-sm font-medium text-neutral-700 transition-colors hover:text-neutral-900 lg:gap-2 dark:text-neutral-300 dark:hover:text-neutral-100" href="{{ $twitchUrl }}" target="_blank" rel="noopener noreferrer">
							<span class="truncate">{{ $username }}</span>
							<x-heroicon-o-arrow-top-right-on-square class="h-3.5 w-3.5 shrink-0 opacity-60 transition-opacity group-hover:opacity-100" />
						</a>
					</span>
					<div class="flex shrink-0 items-center gap-2 font-mono text-xs text-neutral-500 lg:gap-3 dark:text-neutral-400">
						<span class="flex min-w-[3.5rem] items-center justify-end gap-1 tabular-nums lg:min-w-16 lg:gap-1.5">
							<span class="whitespace-nowrap">{{ number_format($raid->viewers) }}</span>
							<x-heroicon-o-eye class="h-3.5 w-3.5 shrink-0 text-neutral-400 dark:text-neutral-500" />
						</span>
						<span class="min-w-[3.5rem] text-right tabular-nums lg:min-w-16">{{ TimeFormatter::formatTimeAgo($raid->timestamp) }}</span>
					</div>
				</li>
			@endforeach
		</ol>
	@endif
</div>
