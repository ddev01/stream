@php
	$record = $getRecord();
	$expiresAt = $record->expires_at;
@endphp

@if ($expiresAt === null)
	<flux:badge color="success">{{ __('Permanent') }}</flux:badge>
@elseif ($record->isExpired())
	<flux:badge color="danger">{{ __('Expired') }}</flux:badge>
@else
	<span class="text-sm text-zinc-600 dark:text-zinc-400">{{ $record->time_remaining_short_label }}</span>
@endif
