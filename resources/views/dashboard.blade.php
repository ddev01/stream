<x-layouts.app :title="__('Dashboard')">
	<div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
		<div class="grid auto-rows-min gap-4 md:grid-cols-3">
			<!-- Points Leaderboard -->
			<div class="relative aspect-video overflow-hidden rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
				<h2 class="mb-4 text-lg font-semibold">Top 5 Points</h2>
				<ol class="space-y-2">
					@foreach ($topPoints as $stat)
						<li class="flex items-center justify-between">
							<span class="flex items-center gap-2">
								<strong class="text-sm font-medium">{{ $loop->iteration }}.</strong>
								<span class="text-sm">{{ $stat->twitchUser->display_name ?? $stat->twitch_user_id }}</span>
							</span>
							<span class="font-mono text-sm">{{ number_format($stat->value) }}</span>
						</li>
					@endforeach
				</ol>

				@if ($userStats && $userPosition)
					<div class="mt-2 border-t border-neutral-200 pt-4 dark:border-neutral-700">
						<h3 class="text-sm font-medium text-neutral-600 dark:text-neutral-400">Your Stats</h3>
						<div class="flex items-center justify-between">
							<span class="text-sm">
								<strong>Position:</strong> #{{ $userPosition }}
							</span>
							<span class="font-mono text-sm">
								{{ number_format($userStats->value) }} points
							</span>
						</div>
					</div>
				@endif
			</div>

			<!-- Watchtime Leaderboard -->
			<div class="relative aspect-video overflow-hidden rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
				<h2 class="mb-4 text-lg font-semibold">Top 5 Watchtime</h2>
				<ol class="space-y-2">
					@foreach ($topWatchtime as $stat)
						<li class="flex items-center justify-between">
							<span class="flex items-center gap-2">
								<strong class="text-sm font-medium">{{ $loop->iteration }}.</strong>
								<span class="text-sm">{{ $stat->twitchUser->display_name ?? $stat->twitch_user_id }}</span>
							</span>
							<span class="font-mono text-sm">{{ $stat->value >= 86400 ? floor($stat->value / 86400) . 'd ' : '' }}{{ floor(($stat->value % 86400) / 3600) . 'h ' . floor(($stat->value % 3600) / 60) . 'm' }}</span>
						</li>
					@endforeach
				</ol>

				@if ($userWatchtimeStats && $userWatchtimePosition)
					<div class="mt-2 border-t border-neutral-200 pt-4 dark:border-neutral-700">
						<h3 class="text-sm font-medium text-neutral-600 dark:text-neutral-400">Your Stats</h3>
						<div class="flex items-center justify-between">
							<span class="text-sm">
								<strong>Position:</strong> #{{ $userWatchtimePosition }}
							</span>
							<span class="font-mono text-sm">
								{{ $userWatchtimeStats->value >= 86400 ? floor($userWatchtimeStats->value / 86400) . 'd ' : '' }}{{ floor(($userWatchtimeStats->value % 86400) / 3600) . 'h ' . floor(($userWatchtimeStats->value % 3600) / 60) . 'm' }}
							</span>
						</div>
					</div>
				@endif
			</div>

			<!-- Top Three Count Leaderboard -->
			<div class="relative aspect-video overflow-hidden rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
				<h2 class="mb-4 text-lg font-semibold">Top 5 Top Three</h2>
				<ol class="space-y-2">
					@foreach ($topTopThree as $stat)
						<li class="flex items-center justify-between">
							<span class="flex items-center gap-2">
								<strong class="text-sm font-medium">{{ $loop->iteration }}.</strong>
								<span class="text-sm">{{ $stat->twitchUser->display_name ?? $stat->twitch_user_id }}</span>
							</span>
							<span class="font-mono text-sm">{{ number_format($stat->value) }}</span>
						</li>
					@endforeach
				</ol>

				@if ($userTopThreeStats && $userTopThreePosition)
					<div class="mt-2 border-t border-neutral-200 pt-4 dark:border-neutral-700">
						<h3 class="text-sm font-medium text-neutral-600 dark:text-neutral-400">Your Stats</h3>
						<div class="flex items-center justify-between">
							<span class="text-sm">
								<strong>Position:</strong> #{{ $userTopThreePosition }}
							</span>
							<span class="font-mono text-sm">
								{{ number_format($userTopThreeStats->value) }} times
							</span>
						</div>
					</div>
				@endif
			</div>
		</div>
		<div class="relative h-full flex-1 overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
			<x-placeholder-pattern class="absolute inset-0 size-full stroke-gray-900/20 dark:stroke-neutral-100/20" />
		</div>
	</div>
</x-layouts.app>
