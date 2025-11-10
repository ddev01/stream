@php
	use App\Support\TimeFormatter;
@endphp

<x-layouts.app :title="__('Home')">
	<div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
		<div class="grid auto-rows-min gap-4 md:grid-cols-3">
			<!-- Points Leaderboard -->
			<x-leaderboard-card title="Top 5 Points" icon="chart-bar" :top-stats="$topPoints" :user-stat="$userStats" :user-position="$userPosition" />

			<!-- Watchtime Leaderboard -->
			<x-leaderboard-card title="Top 5 Watchtime" icon="clock" :top-stats="$topWatchtime" :user-stat="$userWatchtimeStats" :user-position="$userWatchtimePosition" :value-formatter="fn($value) => TimeFormatter::formatWatchtime($value)" />

			<!-- Top Three Count Leaderboard -->
			<x-leaderboard-card title="Top 5 Top Three" icon="trophy" :top-stats="$topTopThree" :user-stat="$userTopThreeStats" :user-position="$userTopThreePosition" />

			<x-leaderboard-card title="Top 5 Gifted Subs" icon="gift" :top-stats="$topGifters" :user-stat="$userGifterStats" :user-position="$userGifterPosition" />

			<x-leaderboard-card title="Top 5 Trivia Wins" icon="academic-cap" :top-stats="$topTriviaWins" :user-stat="$userTriviaWinsStats" :user-position="$userTriviaWinsPosition" />

			<x-leaderboard-card title="Top 5 Raiders" icon="fire" :top-stats="$topRaiders" :user-stat="$userRaiderStats" :user-position="$userRaiderPosition" />

			<x-leaderboard-card title="Top 5 Raider Views" icon="fire" :top-stats="$topRaiderViews" :user-stat="$userRaiderViewsStats" :user-position="$userRaiderViewsPosition" />

			<x-raid-history-card title="Recent Raids" :recent-raids="$recentRaidHistory" />
			<div class="relative  flex items-center justify-center overflow-hidden rounded-xl border border-neutral-200 p-4  dark:border-neutral-700">
				<x-placeholder-pattern class="absolute inset-0 size-full stroke-gray-900/20 dark:stroke-neutral-100/20" />
				<div class="relative z-10 mx-4 w-full max-w-md rounded-lg bg-white shadow-lg dark:bg-zinc-800">
					<flux:callout class="[--callout-background:rgb(255,255,255)] dark:[--callout-background:rgb(38,38,38)]" color="purple" icon="information-circle">
						<flux:callout.heading>More Stats Coming Soon</flux:callout.heading>
						<flux:callout.text>This website is still a work in progress. We're continuously adding new features and statistics to enhance your experience. Stay tuned for updates!</flux:callout.text>
					</flux:callout>
				</div>
			</div>
		</div>
	
	</div>
</x-layouts.app>
