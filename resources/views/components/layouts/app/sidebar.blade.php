<!DOCTYPE html>
<html class="dark" lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
	@include('partials.head')
</head>

<body class="min-h-screen bg-white dark:bg-zinc-800">
	<flux:sidebar class="h-full border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900" sticky stashable>
		<flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

		<a class="me-5 flex items-center space-x-2 rtl:space-x-reverse" href="{{ route('home') }}" wire:navigate>
			<x-app-logo />
		</a>

		<div class="flex h-full flex-col justify-between gap-2">
			<flux:navlist variant="outline">
				<flux:navlist.group class="grid" :heading="__('Platform')">
					<flux:navlist.item icon="home" :href="route('home')" :current="request()->routeIs('home')" wire:navigate>{{ __('Home') }}</flux:navlist.item>
					<flux:navlist.item icon="magnifying-glass" x-data="" x-on:click.prevent="$dispatch('open-search-modal')" wire:navigate="false">{{ __('Search Users') }}</flux:navlist.item>
				</flux:navlist.group>
				<flux:navlist.group class="grid" :heading="__('Leaderboards')">
					<flux:navlist.item icon="chart-bar" :href="route('leaderboards.points')" :current="request()->routeIs('leaderboards.points')" wire:navigate>{{ __('Points') }}</flux:navlist.item>
					<flux:navlist.item icon="clock" :href="route('leaderboards.watchtime')" :current="request()->routeIs('leaderboards.watchtime')" wire:navigate>{{ __('Watchtime') }}</flux:navlist.item>
					<flux:navlist.item icon="trophy" :href="route('leaderboards.top-three')" :current="request()->routeIs('leaderboards.top-three')" wire:navigate>{{ __('Top Three Count') }}</flux:navlist.item>
					<flux:navlist.item icon="gift" :href="route('leaderboards.gifted-subscriptions')" :current="request()->routeIs('leaderboards.gifted-subscriptions')" wire:navigate>{{ __('Gifted Subscriptions') }}</flux:navlist.item>
					<flux:navlist.item icon="academic-cap" :href="route('leaderboards.trivia-wins')" :current="request()->routeIs('leaderboards.trivia-wins')" wire:navigate>{{ __('Trivia Wins') }}</flux:navlist.item>
					<flux:navlist.item icon="fire" :href="route('leaderboards.raiders')" :current="request()->routeIs('leaderboards.raiders')" wire:navigate>{{ __('Raiders') }}</flux:navlist.item>
				</flux:navlist.group>
			</flux:navlist>
			@if (auth()->user()?->isAdmin())
				<flux:navlist variant="outline">
					<flux:navlist.group class="grid" :heading="__('Admin')">
						<flux:navlist.item href="{{ config('pulse.domain') ? 'https://' . config('pulse.domain') : url('/' . config('pulse.path', 'pulse')) }}" icon="chart-bar" :current="request()->getHost() === config('pulse.domain')">{{ __('Pulse') }}</flux:navlist.item>
						<flux:navlist.item href="{{ config('horizon.domain') ? 'https://' . config('horizon.domain') : url('/' . config('horizon.path', 'horizon')) }}" icon="squares-2x2" :current="request()->getHost() === config('horizon.domain')">{{ __('Horizon') }}</flux:navlist.item>
						<flux:navlist.item href="{{ config('telescope.domain') ? 'https://' . config('telescope.domain') : url('/' . config('telescope.path', 'telescope')) }}" icon="magnifying-glass" :current="request()->getHost() === config('telescope.domain')">{{ __('Telescope') }}</flux:navlist.item>
						<flux:navlist.item href="https://v-a9.sentry.io/issues/" icon="exclamation-triangle" target="_blank">{{ __('Sentry') }}</flux:navlist.item>
					</flux:navlist.group>
				</flux:navlist>
			@endif
		</div>

		{{-- <flux:spacer /> --}}

		{{-- <flux:navlist variant="outline">
                <flux:navlist.item icon="folder-git-2" href="https://github.com/laravel/livewire-starter-kit" target="_blank">
                {{ __('Repository') }}
                </flux:navlist.item>

                <flux:navlist.item icon="book-open-text" href="https://laravel.com/docs/starter-kits#livewire" target="_blank">
                {{ __('Documentation') }}
                </flux:navlist.item>
            </flux:navlist> --}}

		<!-- Desktop User Menu -->

		@auth
			<flux:dropdown class="hidden lg:block" position="bottom" align="start">
				<flux:profile data-test="sidebar-menu-button" :name="auth()->user()->name" :initials="auth()->user()->initials()" icon:trailing="chevrons-up-down" />

				<flux:menu class="w-[220px]">
					<flux:menu.radio.group>
						<div class="p-0 text-sm font-normal">
							<div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
								<span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
									<span class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white">
										{{ auth()->user()->initials() }}
									</span>
								</span>

								<div class="grid flex-1 text-start text-sm leading-tight">
									<span class="truncate font-semibold">{{ auth()->user()->name }}</span>
								</div>
							</div>
						</div>
					</flux:menu.radio.group>

					<flux:menu.separator />

					<flux:menu.radio.group>
						<flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>{{ __('Settings') }}</flux:menu.item>
					</flux:menu.radio.group>

					<flux:menu.separator />

					<form class="w-full" method="POST" action="{{ route('logout') }}">
						@csrf
						<flux:menu.item class="w-full" data-test="logout-button" type="submit" as="button" icon="arrow-right-start-on-rectangle">
							{{ __('Log Out') }}
						</flux:menu.item>
					</form>
				</flux:menu>
			</flux:dropdown>
		@else
			<x-twitch-sign-in-button />
		@endauth
	</flux:sidebar>

	<!-- Mobile User Menu -->
	<flux:header class="lg:hidden">
		<flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

		@auth
			<flux:spacer />

			<flux:dropdown position="top" align="end">
				<flux:profile :initials="auth()->user()->initials()" icon-trailing="chevron-down" />

				<flux:menu>
					<flux:menu.radio.group>
						<div class="p-0 text-sm font-normal">
							<div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
								<span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
									<span class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white">
										{{ auth()->user()->initials() }}
									</span>
								</span>

								<div class="grid flex-1 text-start text-sm leading-tight">
									<span class="truncate font-semibold">{{ auth()->user()->name }}</span>
								</div>
							</div>
						</div>
					</flux:menu.radio.group>

					<flux:menu.separator />

					<flux:menu.radio.group>
						<flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>{{ __('Settings') }}</flux:menu.item>
						@if (auth()->user()?->isAdmin())
							<flux:menu.item href="{{ config('pulse.domain') ? 'https://' . config('pulse.domain') : url('/' . config('pulse.path', 'pulse')) }}" icon="chart-bar">{{ __('Pulse') }}</flux:menu.item>
							<flux:menu.item href="{{ config('horizon.domain') ? 'https://' . config('horizon.domain') : url('/' . config('horizon.path', 'horizon')) }}" icon="squares-2x2">{{ __('Horizon') }}</flux:menu.item>
							<flux:menu.item href="{{ config('telescope.domain') ? 'https://' . config('telescope.domain') : url('/' . config('telescope.path', 'telescope')) }}" icon="magnifying-glass">{{ __('Telescope') }}</flux:menu.item>
							<flux:menu.item href="https://v-a9.sentry.io/issues/" icon="exclamation-triangle" target="_blank">{{ __('Sentry') }}</flux:menu.item>
						@endif
					</flux:menu.radio.group>

					<flux:menu.separator />

					<form class="w-full" method="POST" action="{{ route('logout') }}">
						@csrf
						<flux:menu.item class="w-full" data-test="logout-button" type="submit" as="button" icon="arrow-right-start-on-rectangle">
							{{ __('Log Out') }}
						</flux:menu.item>
					</form>
				</flux:menu>
			</flux:dropdown>
		@endauth
	</flux:header>

	{{ $slot }}

	<livewire:search-user />

	@fluxScripts
</body>

</html>
