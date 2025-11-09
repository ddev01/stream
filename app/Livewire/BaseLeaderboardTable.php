<?php

namespace App\Livewire;

use App\Livewire\Concerns\FormatsUserLinks;
use App\Models\TwitchUserStat;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

abstract class BaseLeaderboardTable extends DataTableComponent
{
    use FormatsUserLinks;

    protected $model = TwitchUserStat::class;

    public int $rankCounter = 0;

    /**
     * Get the stat name for this leaderboard (e.g., 'points', 'watchtime', 'topThreeCount')
     */
    abstract protected function getStatName(): string;

    /**
     * Get the value column title (e.g., 'Points', 'Watchtime', 'Top Three Count')
     */
    abstract protected function getValueColumnTitle(): string;

    /**
     * Format the value for display
     */
    abstract protected function formatValue($value): string;

    /**
     * Configure the table component
     */
    public function configure(): void
    {
        $this->setPrimaryKey('id')
            ->setDefaultSort('value', 'desc');
    }

    /**
     * Build the query for the table
     */
    public function builder(): Builder
    {
        $this->rankCounter = 0;

        $query = TwitchUserStat::query()
            ->forStat($this->getStatName())
            ->with(['twitchUser.user']);

        // If there's a search term, add the join early so it's available for count queries
        // The searchable callback will add the WHERE clause, but we need the join in the base query
        if ($this->hasSearch() && ! empty($this->getSearch())) {
            $query->join('twitch_users', 'twitch_user_stats.twitch_user_id', '=', 'twitch_users.id')
                ->select('twitch_user_stats.*');
        }

        // Apply default numeric sort if no user sort is active
        if (! $this->hasSorts()) {
            $query->orderedByValue('desc');
        }

        return $query;
    }

    /**
     * Define the columns for the table
     */
    public function columns(): array
    {
        return [
            $this->getRankColumn(),
            $this->getUsernameColumn(),
            $this->getValueColumn(),
        ];
    }

    /**
     * Get the rank column definition
     */
    protected function getRankColumn(): Column
    {
        return Column::make('Rank', 'id')
            ->format(function ($value, $row, Column $column) {
                $page = $this->getPage() ?? 1;
                $perPage = $this->getPerPage() ?? 10;

                return ($page - 1) * $perPage + (++$this->rankCounter);
            })
            ->unclickable();
    }

    /**
     * Get the username column definition
     */
    protected function getUsernameColumn(): Column
    {
        return Column::make('Username', 'twitch_user_id')
            ->format(function ($value, $row, Column $column) {
                return view('livewire.tables.user-link', $this->formatUserLink($row));
            })
            ->sortable(function (Builder $query, string $direction) {
                // Join for sorting, but keep eager loading to avoid N+1 queries
                // Eloquent will still eager load relationships after the join
                // Only add join if it's not already present
                $joins = $query->getQuery()->joins ?? [];
                if (empty($joins)) {
                    $query->join('twitch_users', 'twitch_user_stats.twitch_user_id', '=', 'twitch_users.id')
                        ->select('twitch_user_stats.*');
                }

                return $query->orderByRaw('LOWER(twitch_users.display_name) '.$direction)
                    ->with(['twitchUser.user']);
            })
            ->searchable(function (Builder $query, $searchTerm) {
                // Use join instead of whereHas for better performance (avoids subquery)
                // Use ILIKE for PostgreSQL, LIKE for SQLite (SQLite LIKE is case-insensitive)
                $dbDriver = $query->getConnection()->getDriverName();
                $operator = $dbDriver === 'pgsql' ? 'ilike' : 'like';

                // Only add join if it's not already present (builder() may have added it)
                $joins = $query->getQuery()->joins ?? [];
                if (empty($joins)) {
                    $query->join('twitch_users', 'twitch_user_stats.twitch_user_id', '=', 'twitch_users.id')
                        ->select('twitch_user_stats.*');
                }

                return $query->where('twitch_users.display_name', $operator, "%{$searchTerm}%")
                    ->with(['twitchUser.user']);
            });
    }

    /**
     * Get the value column definition
     */
    protected function getValueColumn(): Column
    {
        return Column::make($this->getValueColumnTitle(), 'value')
            ->sortable(function (Builder $query, string $direction) {
                // Cast to numeric for proper sorting (column may be TEXT in database)
                $joins = $query->getQuery()->joins ?? [];
                if (empty($joins)) {
                    return $query->orderByRaw('CAST(value AS INTEGER) '.$direction);
                }

                // If there are joins, ensure we select the main table columns and cast to numeric
                return $query->select('twitch_user_stats.*')
                    ->orderByRaw('CAST(twitch_user_stats.value AS INTEGER) '.$direction);
            })
            ->setSortingPillDirections('0-9', '9-0')
            ->format(fn ($value) => $this->formatValue($value));
    }
}
