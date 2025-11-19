<!-- Trigger is handled by the sidebar link -->

<flux:modal wire:model="isOpen" name="search-users-modal" focusable class="!w-[620px]">
    <div class="space-y-4">
        <div>
            <flux:heading size="lg">Search Users</flux:heading>
            <flux:subheading>Search by username or paste a Twitch ID</flux:subheading>
        </div>

        <flux:field>
                <input
                    type="text"
                    wire:model.live.debounce.300ms="query"
                    placeholder="Type username or paste Twitch ID..."
                    autofocus
                    x-ref="searchInput"
                    x-on:keydown.escape="$wire.close()"
                    x-on:keydown.arrow-down.prevent.stop="$wire.moveSelection('down')"
                    x-on:keydown.arrow-up.prevent.stop="$wire.moveSelection('up')"
                    x-on:keydown.enter.prevent.stop="
                        if ($wire.results.length === 1) {
                            $wire.navigateToUser(0);
                        } else if ($wire.selectedIndex >= 0) {
                            $wire.navigateToUser();
                        } else if ($wire.results.length > 0) {
                            $wire.navigateToUser(0);
                        }
                    "
                    class="block w-full rounded-lg border border-neutral-200 bg-white px-3 py-2 text-sm shadow-sm transition-colors focus:border-neutral-500 focus:ring-1 focus:ring-neutral-500 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-100 dark:focus:border-neutral-400 dark:focus:ring-neutral-400"
                />
        </flux:field>

        <div class="min-h-[360px]">
            <div wire:loading class="flex flex-col gap-3 py-6">
                @for ($i = 0; $i < 3; $i++)
                    <div class="flex items-center gap-3 px-4 py-3">
                        <div class="h-10 w-10 shrink-0 animate-pulse rounded-full bg-neutral-200 dark:bg-neutral-700"></div>
                        <div class="flex-1 space-y-2">
                            <div class="h-4 w-32 animate-pulse rounded bg-neutral-200 dark:bg-neutral-700"></div>
                            <div class="h-3 w-24 animate-pulse rounded bg-neutral-200 dark:bg-neutral-700"></div>
                        </div>
                    </div>
                @endfor
            </div>

            <div wire:loading.remove>
                @if (!empty($query) && !$isSearching)
                    @if (count($results) > 0)
                        <div class="max-h-[320px] overflow-y-auto rounded-lg border border-neutral-200 dark:border-neutral-700">
                            <div class="divide-y divide-neutral-200 dark:divide-neutral-700">
                                @foreach ($results as $index => $user)
                                    <button
                                        type="button"
                                        wire:click="navigateToUser({{ $index }})"
                                        class="w-full px-4 py-3 text-left transition-colors hover:bg-neutral-50 dark:hover:bg-neutral-800 {{ $selectedIndex === $index ? 'bg-neutral-100 dark:bg-neutral-800' : '' }}"
                                        x-on:mouseenter="$wire.set('selectedIndex', {{ $index }})"
                                    >
                                        <div class="flex items-center gap-3">
                                            @if (!empty($user['profile_image_url']))
                                                <img
                                                    src="{{ $user['profile_image_url'] }}"
                                                    alt="{{ $user['display_name'] }}"
                                                    class="h-10 w-10 rounded-full"
                                                />
                                            @else
                                                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-neutral-200 text-sm font-semibold dark:bg-neutral-700">
                                                    @php
                                                        $initials = \Illuminate\Support\Str::of($user['display_name'] ?? '')
                                                            ->explode(' ')
                                                            ->take(2)
                                                            ->map(fn ($word) => \Illuminate\Support\Str::substr($word, 0, 1))
                                                            ->implode('');
                                                    @endphp
                                                    {{ $initials }}
                                                </div>
                                            @endif
                                            <div class="flex-1 min-w-0">
                                                <div class="font-semibold text-neutral-900 dark:text-neutral-100">
                                                    {{ $user['display_name'] }}
                                                </div>
                                                @if (!empty($user['broadcaster_type']))
                                                    <div class="text-xs text-neutral-600 dark:text-neutral-400">
                                                        {{ ucfirst($user['broadcaster_type']) }}
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="text-xs text-neutral-500 dark:text-neutral-400">
                                                Press Enter
                                            </div>
                                        </div>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <div class="rounded-lg border border-neutral-200 bg-neutral-50 p-8 text-center dark:border-neutral-700 dark:bg-neutral-900">
                            <div class="text-sm text-neutral-600 dark:text-neutral-400">
                                No users found matching "{{ $query }}"
                            </div>
                            <div class="mt-2 text-xs text-neutral-500 dark:text-neutral-500">
                                Try a different username or paste a Twitch ID
                            </div>
                        </div>
                    @endif
                @endif

                @if (empty($query) && !$isSearching)
                    <div class="rounded-lg border border-neutral-200 bg-neutral-50 p-8 text-center dark:border-neutral-700 dark:bg-neutral-900">
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
        (function () {
            if (window.__searchShortcutBound) {
                return;
            }

            window.__searchShortcutBound = true;

            window.addEventListener('keydown', function (event) {
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
        })();
    </script>
@endonce
