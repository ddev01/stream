<x-layouts.app :title="__('Trivia Wins Leaderboard')">
	<div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
		<div class="relative h-full flex-1 overflow-hidden rounded-xl border border-neutral-200 p-8 dark:border-neutral-700">
			<livewire:trivia-wins-leaderboard-table />
		</div>
	</div>
</x-layouts.app>

