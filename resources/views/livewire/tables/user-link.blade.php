@if($hasUser && $twitchId)
    <a href="{{ route('users.show', $twitchId) }}" class="text-blue-600 dark:text-blue-400 hover:underline">
        {{ $displayName }}
    </a>
@else
    <span>{{ $displayName }}</span>
@endif

