<?php

namespace App\Livewire;

use App\Models\TwitchUser;
use Livewire\Component;

class SearchUser extends Component
{
    public string $query = '';

    public bool $isOpen = false;

    public int $selectedIndex = -1;

    public $results = [];

    public bool $isSearching = false;

    protected $listeners = ['open-search-modal' => 'open'];

    public function updatedQuery(): void
    {
        $this->selectedIndex = -1;
        $this->isSearching = true;

        if (empty($this->query)) {
            $this->results = [];
            $this->isSearching = false;
            return;
        }

        // Check if query looks like a twitch_id (alphanumeric, typically numeric)
        if (preg_match('/^[a-zA-Z0-9]+$/', $this->query) && strlen($this->query) > 5) {
            // Try direct twitch_id lookup
            $user = TwitchUser::where('twitch_id', $this->query)->first();
            if ($user) {
                $this->results = [$user->toArray()];
                $this->isSearching = false;
                return;
            }
        }

        // Search by display_name
        $dbDriver = \Illuminate\Support\Facades\DB::connection()->getDriverName();
        $operator = $dbDriver === 'pgsql' ? 'ilike' : 'like';

        $query = TwitchUser::query()
            ->where('display_name', $operator, "%{$this->query}%");

        // Order by exact match first, then partial matches
        if ($dbDriver === 'pgsql') {
            $query->orderByRaw('CASE WHEN display_name ilike ? THEN 0 ELSE 1 END', ["{$this->query}"]);
        } else {
            $query->orderByRaw('CASE WHEN LOWER(display_name) = LOWER(?) THEN 0 ELSE 1 END', [$this->query]);
        }

        $this->results = $query
            ->orderBy('display_name')
            ->limit(8)
            ->get()
            ->toArray();

        $this->isSearching = false;
    }

    /**
     * Open the modal
     */
    public function open(): void
    {
        $this->isOpen = true;
        $this->query = '';
        $this->results = [];
        $this->selectedIndex = -1;
    }

    /**
     * Close the modal
     */
    public function close(): void
    {
        $this->isOpen = false;
        $this->query = '';
        $this->results = [];
        $this->selectedIndex = -1;
    }

    /**
     * Navigate to selected user
     */
    public function navigateToUser(?int $index = null): void
    {
        $index = $index ?? $this->selectedIndex;

        if ($index < 0 || $index >= count($this->results)) {
            return;
        }

        $user = $this->results[$index];
        $twitchId = $user['twitch_id'] ?? null;

        if ($twitchId) {
            $this->close();
            $this->redirect(route('users.show', $twitchId), navigate: true);
        }
    }

    /**
     * Handle keyboard navigation
     */
    public function moveSelection(string $direction): void
    {
        if (count($this->results) === 0) {
            return;
        }

        if ($direction === 'up') {
            $this->selectedIndex = $this->selectedIndex <= 0
                ? count($this->results) - 1
                : $this->selectedIndex - 1;
        } elseif ($direction === 'down') {
            $this->selectedIndex = $this->selectedIndex >= count($this->results) - 1
                ? 0
                : $this->selectedIndex + 1;
        }

        $this->skipRender();
    }

    public function render()
    {
        return view('livewire.search-user');
    }
}
