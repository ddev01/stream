<section class="w-full">
	<div class="mx-auto max-w-4xl space-y-6 p-6">
		<div>
			<flux:heading size="lg">{{ __('File Sharing') }}</flux:heading>
			<flux:subheading>{{ __('Upload files and share them with a simple URL') }}</flux:subheading>
		</div>

		@if (session('error'))
			<flux:callout variant="danger" icon="exclamation-triangle">
				{{ session('error') }}
			</flux:callout>
		@endif

		@if ($shareUrl)
			<flux:callout variant="success" icon="check-circle" x-data="{ copied: false }" x-init="$nextTick(() => {
    $refs.shareUrlInput?.focus?.();
    $refs.shareUrlInput?.select?.();
})">
				<div class="space-y-4">
					<p class="font-semibold">{{ __('File uploaded successfully!') }}</p>
					<div class="flex items-center gap-2">
						<flux:input class="flex-1 font-mono text-sm" type="text" :value="$shareUrl" readonly x-ref="shareUrlInput" />
						<flux:button variant="primary" icon="clipboard-document" x-on:click="navigator.clipboard.writeText($refs.shareUrlInput.value); copied = true; setTimeout(() => copied = false, 2000)">
							<span x-show="!copied">{{ __('Copy') }}</span>
							<span x-show="copied" x-cloak>{{ __('Copied!') }}</span>
						</flux:button>
					</div>
					<flux:button variant="ghost" wire:click="resetForm" icon="arrow-path">
						{{ __('Upload Another File') }}
					</flux:button>
				</div>
			</flux:callout>
		@endif

		<div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
			<div class="space-y-6" x-data="{
    isDragging: false,
    lastUploadError: null,
    handleDrop(event) {
        this.isDragging = false;
        const files = event.dataTransfer.files;
        if (files.length > 0) {
            const fileInput = this.$refs.fileInput;
            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(files[0]);
            fileInput.files = dataTransfer.files;
            fileInput.dispatchEvent(new Event('change', { bubbles: true }));
        }
    }
}" x-on:dragover.prevent="isDragging = true" x-on:dragleave.prevent="isDragging = false" x-on:drop.prevent="handleDrop($event)" x-on:livewire-upload-error.window="lastUploadError = 'Livewire upload failed. This is usually caused by Livewire temp upload limits or server request limits (413/timeout). Check logs/Network tab.'" x-on:livewire-upload-finish.window="lastUploadError = null">
				<flux:field :label="__('Select File')">
					<div class="relative">
						<div class="rounded-lg border-2 border-dashed transition-colors" :class="isDragging ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20' : 'border-zinc-300 dark:border-zinc-600'">
							<input class="absolute inset-0 w-full cursor-pointer opacity-0" type="file" x-ref="fileInput" wire:model="file" wire:loading.attr="disabled" accept="video/*,image/*,application/pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.zip,.rar,.7z" />
							<div class="pointer-events-none flex flex-col items-center justify-center py-12 text-center">
								<flux:icon class="mb-3 size-10 text-zinc-400" name="arrow-up-tray" />
								<p class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
									<span class="text-primary-600 dark:text-primary-400">{{ __('Click to upload') }}</span>
									{{ __('or drag and drop') }}
								</p>
								<p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
									{{ __('Maximum file size: 5GB') }}
								</p>
							</div>
						</div>
					</div>
					<flux:description class="mt-2">
						{{ __('Upload starts automatically after selecting/dropping a file. Supported formats: videos, images, documents, archives.') }}
					</flux:description>
				</flux:field>

				<div class="space-y-2" wire:loading wire:target="file">
					<div class="flex items-center justify-between text-sm">
						<span class="text-zinc-600 dark:text-zinc-400">{{ __('Preparing upload...') }}</span>
					</div>
					<div class="h-2 w-full overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
						<div class="bg-primary-600 h-full animate-pulse" style="width: 50%"></div>
					</div>
				</div>

				@error('file')
					<flux:callout variant="danger" icon="exclamation-triangle">
						{{ $message }}
					</flux:callout>
				@enderror

				@if (app()->environment('local'))
					<div x-show="lastUploadError" x-cloak>
						<flux:callout variant="warning" icon="information-circle">
							<span x-text="lastUploadError"></span>
						</flux:callout>
					</div>
				@endif

				@if ($file)
					<div class="rounded-lg bg-zinc-50 p-4 dark:bg-zinc-800">
						<div class="flex items-center justify-between">
							<div>
								<p class="font-medium text-zinc-900 dark:text-zinc-100">{{ $file->getClientOriginalName() }}</p>
								<p class="text-sm text-zinc-500 dark:text-zinc-400">
									@php
										$sizeInMB = $file->getSize() / 1024 / 1024;
										$sizeInGB = $file->getSize() / 1024 / 1024 / 1024;
									@endphp
									@if ($sizeInGB >= 1)
										{{ number_format($sizeInGB, 2) }} GB
									@else
										{{ number_format($sizeInMB, 2) }} MB
									@endif
								</p>
							</div>
						</div>
					</div>
				@endif

				<div class="space-y-2" wire:loading wire:target="upload">
					<div class="flex items-center justify-between text-sm">
						<span class="text-zinc-600 dark:text-zinc-400">{{ __('Uploading...') }}</span>
					</div>
					<div class="h-2 w-full overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
						<div class="bg-primary-600 h-full animate-pulse" style="width: 75%"></div>
					</div>
				</div>
			</div>
		</div>
	</div>
</section>
