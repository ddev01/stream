<!-- Trigger is handled by the sidebar link -->

<flux:modal class="!w-[calc(100vw-2rem)] !max-w-[620px] sm:!w-full" name="search-users-modal" wire:model="isOpen" focusable>
	<div class="space-y-4" x-data="searchLoadingState()">
		<div>
			<flux:heading size="lg">Search Users</flux:heading>
			<flux:subheading>Search by username or paste a Twitch ID</flux:subheading>
		</div>

		<flux:field>
			<input class="block w-full rounded-lg border border-neutral-200 bg-white px-3 py-2 text-sm shadow-sm transition-colors focus:border-neutral-500 focus:ring-1 focus:ring-neutral-500 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-100 dark:focus:border-neutral-400 dark:focus:ring-neutral-400" type="text" wire:model.live.debounce.300ms="query" placeholder="Type username or paste Twitch ID..." autofocus x-ref="searchInput" x-on:keydown.escape="$wire.close()" x-on:keydown.arrow-down.prevent.stop="$wire.moveSelection('down')" x-on:keydown.arrow-up.prevent.stop="$wire.moveSelection('up')" x-on:keydown.enter.prevent.stop="
                    if ($wire.results.length === 1) {
                        $wire.navigateToUser(0);
                    } else if ($wire.selectedIndex >= 0) {
                        $wire.navigateToUser();
                    } else if ($wire.results.length > 0) {
                        $wire.navigateToUser(0);
                    }
                " x-on:input="if ($event.target.value.length > 0) loading = true" />
		</flux:field>

		<div class="relative min-h-[326px]">
			<!-- Show skeleton when Livewire is loading -->
			<div class="absolute inset-0 z-10" x-show="loading" x-transition x-transition:enter.duration.200ms x-transition:leave.duration.150ms>
				<div class="max-h-[280px] overflow-y-auto rounded-lg border border-neutral-200 bg-white sm:max-h-[320px] dark:border-neutral-700 dark:bg-neutral-900">
					<div class="divide-y divide-neutral-200 dark:divide-neutral-700">
						@for ($i = 0; $i < 5; $i++)
							<div class="w-full px-3 py-2.5 sm:px-4 sm:py-3">
								<div class="flex items-center gap-2 sm:gap-3">
									<div class="flex h-8 w-8 shrink-0 animate-pulse items-center justify-center rounded-full bg-neutral-200 sm:h-10 sm:w-10 dark:bg-neutral-700"></div>
									<div class="min-w-0 flex-1">
										<div class="h-4 w-24 animate-pulse rounded bg-neutral-200 sm:h-5 sm:w-32 dark:bg-neutral-700"></div>
									</div>
									<div class="hidden h-3 w-16 animate-pulse rounded bg-neutral-200 sm:block sm:w-20 dark:bg-neutral-700"></div>
								</div>
							</div>
						@endfor
					</div>
				</div>
			</div>

			<!-- Show results when not loading and we have results -->
			<div class="relative" x-show="!loading" x-transition x-transition:enter.duration.200ms x-transition:leave.duration.150ms>
				@if (!empty($query))
					@if (count($results) > 0)
						<div class="overflow-y-auto rounded-lg border border-neutral-200 max-h-[319px] sm:max-h-[326px] dark:border-neutral-700">
							<div class="divide-y divide-neutral-200 dark:divide-neutral-700">
								@foreach ($results as $index => $user)
									<button class="{{ $selectedIndex === $index ? 'bg-neutral-100 dark:bg-neutral-800' : '' }} group w-full cursor-pointer px-3 py-2.5 text-left transition-all duration-150 hover:bg-neutral-100 hover:shadow-sm sm:px-4 sm:py-3 dark:hover:bg-neutral-900/80" type="button" wire:click="navigateToUser({{ $index }})" x-on:mouseenter="$wire.set('selectedIndex', {{ $index }})">
										<div class="flex items-center gap-2 transition-transform duration-150 group-hover:translate-x-2 sm:gap-3">
											@if (!empty($user['profile_image_url']))
												<img class="h-8 w-8 rounded-full sm:h-10 sm:w-10" src="{{ $user['profile_image_url'] }}" alt="{{ $user['display_name'] }}" />
											@else
												<div class="flex h-8 w-8 items-center justify-center rounded-full bg-neutral-200 text-xs font-semibold sm:h-10 sm:w-10 sm:text-sm dark:bg-neutral-700">
													@php
														$initials = \Illuminate\Support\Str::of($user['display_name'] ?? '')
														    ->explode(' ')
														    ->take(2)
														    ->map(fn($word) => \Illuminate\Support\Str::substr($word, 0, 1))
														    ->implode('');
													@endphp
													{{ $initials }}
												</div>
											@endif
											<div class="min-w-0 flex-1">
												<div class="text-sm font-semibold text-neutral-900 sm:text-base dark:text-neutral-100">
													{{ $user['display_name'] }}
												</div>
												@if (!empty($user['broadcaster_type']))
													<div class="hidden text-xs text-neutral-600 sm:block dark:text-neutral-400">
														{{ ucfirst($user['broadcaster_type']) }}
													</div>
												@endif
											</div>
											<div class="hidden text-xs text-neutral-500 sm:block dark:text-neutral-400">
												Press Enter
											</div>
										</div>
									</button>
								@endforeach
							</div>
						</div>
					@else
						<div class="rounded-lg border border-neutral-200 bg-neutral-50 p-6 text-center sm:p-8 dark:border-neutral-700 dark:bg-neutral-900">
							<div class="text-sm text-neutral-600 dark:text-neutral-400">
								No users found matching "{{ $query }}"
							</div>
							<div class="mt-2 text-xs text-neutral-500 dark:text-neutral-500">
								Try a different username or paste a Twitch ID
							</div>
						</div>
					@endif
				@endif

				@if (empty($query))
					<div class="rounded-lg border border-neutral-200 bg-neutral-50 p-6 text-center sm:p-8 dark:border-neutral-700 dark:bg-neutral-900">
						<div class="text-sm text-neutral-600 dark:text-neutral-400">
							Start typing to search for users
						</div>
						<div class="mt-2 text-xs text-neutral-500 dark:text-neutral-500">
							You can search by username or paste a Twitch ID directly
						</div>
					</div>
				@endif
			</div>
		</div>

		<div class="flex justify-end">
			<flux:modal.close>
				<flux:button variant="ghost">Close</flux:button>
			</flux:modal.close>
		</div>
	</div>
</flux:modal>

@once
	<script>
		// Alpine.js component for managing search loading state
		function searchLoadingState() {
			return {
				loading: false,
				init() {
					// Turn off loading after any Livewire request completes
					Livewire.hook('request', ({
						respond,
						succeed,
						fail
					}) => {
						respond(() => {
							this.loading = false;
						});

						succeed(() => {
							this.loading = false;
						});

						fail(() => {
							this.loading = false;
						});
					});
				}
			}
		}

		// Keyboard shortcut handler
		(function() {
			if (window.__searchShortcutBound) {
				return;
			}

			window.__searchShortcutBound = true;

			window.addEventListener('keydown', function(event) {
				if (
					event.target.tagName === 'INPUT' ||
					event.target.tagName === 'TEXTAREA' ||
					event.target.isContentEditable
				) {
					return;
				}

				if ((event.metaKey || event.ctrlKey) && event.key === 'k') {
					event.preventDefault();
					window.dispatchEvent(new CustomEvent('open-search-modal'));
				}
			});
		})
		();
	</script>
@endonce
