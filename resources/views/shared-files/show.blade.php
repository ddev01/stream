<x-layouts.app :title="$sharedFile->original_filename">
	<div class="mx-auto max-w-6xl space-y-6 p-6">
		<div class="space-y-2">
			<flux:heading size="lg">{{ $sharedFile->original_filename }}</flux:heading>
			<flux:subheading>{{ $sharedFile->human_readable_size }} • {{ $sharedFile->mime_type }}</flux:subheading>
		</div>

		@if ($sharedFile->isVideo())
			<div class="rounded-lg border border-zinc-200 bg-zinc-900 p-4 dark:border-zinc-700">
				<video class="max-h-[70vh] w-full rounded-lg object-contain" controls preload="metadata">
					<source src="{{ $sharedFile->file_url }}" type="{{ $sharedFile->mime_type }}">
					{{ __('Your browser does not support the video tag.') }}
				</video>
			</div>
		@elseif ($sharedFile->isImage())
			<div class="flex justify-center rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
				<img class="max-h-[80vh] max-w-full rounded-lg object-contain" src="{{ $sharedFile->file_url }}" alt="{{ $sharedFile->original_filename }}" />
			</div>
		@else
			<div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
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

		<div class="flex items-center justify-center gap-4">
			<flux:button variant="ghost" icon="arrow-down-tray" :href="$sharedFile->download_url">
				{{ __('Download') }}
			</flux:button>
		</div>
	</div>
</x-layouts.app>
