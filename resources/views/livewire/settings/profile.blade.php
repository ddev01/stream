<?php

use Livewire\Volt\Component;

new class extends Component
{
    // Profile information is read-only since we use OAuth
}; ?>

<section class="w-full">
	@include('partials.settings-heading')

	<x-settings.layout :heading="__('Profile')" :subheading="__('Your account information')">
		<div class="my-6 w-full space-y-6">
			<div>
				<flux:field :label="__('Name')">
					<flux:input type="text" :value="auth()->user()->name" disabled />
				</flux:field>
				<flux:description class="mt-2">
					{{ __('Your name is managed through your Twitch account and cannot be changed here.') }}
				</flux:description>
			</div>
		</div>

		<livewire:settings.delete-user-form />
	</x-settings.layout>
</section>
