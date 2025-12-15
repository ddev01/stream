<x-layouts.app :title="$sharedFile->original_filename">
	<div class="mx-auto max-w-6xl space-y-6 p-6">
		<div class="rounded-lg border border-zinc-200 bg-white/70 p-4 shadow-sm backdrop-blur dark:border-zinc-700 dark:bg-zinc-900/70">
			<div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
				<div class="min-w-0 space-y-2">
					<flux:heading class="truncate" size="lg">{{ $sharedFile->original_filename }}</flux:heading>
					<div class="flex flex-wrap items-center gap-2">
						<flux:badge class="tabular-nums" color="zinc">{{ $sharedFile->human_readable_size }}</flux:badge>
						<flux:badge color="zinc">{{ $sharedFile->mime_type }}</flux:badge>
					</div>
				</div>

				<div class="flex items-center gap-2" x-data="{ copied: false }">
					<flux:button variant="ghost" icon="clipboard-document" x-on:click="navigator.clipboard.writeText(window.location.href); copied = true; setTimeout(() => copied = false, 2000)">
						<span x-show="!copied">{{ __('Copy link') }}</span>
						<span x-show="copied" x-cloak>{{ __('Copied!') }}</span>
					</flux:button>
					<flux:button variant="primary" icon="arrow-down-tray" :href="$sharedFile->download_url">
						{{ __('Download') }}
					</flux:button>
				</div>
			</div>
		</div>

		@if ($sharedFile->isVideo())
			<div class="rounded-lg border border-zinc-200 bg-zinc-900 p-4 shadow-sm dark:border-zinc-700">
				<video class="max-h-[70vh] w-full rounded-lg bg-black object-contain" controls preload="metadata">
					<source src="{{ $sharedFile->file_url }}" type="{{ $sharedFile->mime_type }}">
					{{ __('Your browser does not support the video tag.') }}
				</video>
			</div>
		@elseif ($sharedFile->isImage())
			<div class="flex justify-center rounded-lg border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
				<img class="max-h-[80vh] max-w-full rounded-lg object-contain" src="{{ $sharedFile->file_url }}" alt="{{ $sharedFile->original_filename }}" loading="lazy" />
			</div>
		@else
			<div class="rounded-lg border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
				<div class="flex flex-col items-center justify-center space-y-4 py-12 text-center">
					<flux:icon class="size-16 text-zinc-400" name="document" />
					<div class="space-y-2">
						<p class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">
							{{ __('File Preview Not Available') }}
						</p>
						<p class="text-sm text-zinc-500 dark:text-zinc-400">
							{{ __('This file type cannot be previewed in the browser.') }}
						</p>
					</div>
					<flux:button variant="primary" icon="arrow-down-tray" :href="$sharedFile->download_url">
						{{ __('Download File') }}
					</flux:button>
				</div>
			</div>
		@endif

	</div>
</x-layouts.app>

