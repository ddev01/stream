@if($hasUser && $userId)
    <a href="{{ route('users.show', $userId) }}" class="text-blue-600 dark:text-blue-400 hover:underline">
        {{ $displayName }}
    </a>
@else
    <span>{{ $displayName }}</span>
@endif

