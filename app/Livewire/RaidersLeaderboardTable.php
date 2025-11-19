<?php

namespace App\Livewire;

use App\Livewire\Concerns\FormatsUserLinks;
use App\Models\TwitchUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

// Dummy model to prevent Eloquent from adding table columns
class RaidersLeaderboardDummy extends Model
{
    protected $table = 'raiders_leaderboard_data';

    public $timestamps = false;
}

class RaidersLeaderboardTable extends DataTableComponent
{
    use FormatsUserLinks;

    /**
     * Cache for user ranks - MUST be public for Livewire to persist it
     */
    public array $rankCache = [];

    /**
     * Configure the table component
     */
    public function configure(): void
    {
        $this->setPrimaryKey('user_id')
            ->setDefaultSort('raid_count', 'desc');
    }

    /**
     * Build the query for the table
     */
    public function builder(): Builder
    {
        // Reset and rebuild rank cache based on CURRENT sort order
        $this->buildRankCache();

        // Build the subquery for raid counts and total viewers
        $subquery = DB::table('raid_history')
            ->selectRaw('user_id, COUNT(*) as raid_count, SUM(viewers) as total_viewers')
            ->whereNotNull('user_id')
            ->groupBy('user_id');

        // Build the main query
        $query = DB::table(DB::raw("({$subquery->toSql()}) as raid_stats"))
            ->mergeBindings($subquery)
            ->join('twitch_users', 'raid_stats.user_id', '=', 'twitch_users.twitch_id')
            ->select(
                'raid_stats.user_id',
                'raid_stats.raid_count',
                'raid_stats.total_viewers',
                'twitch_users.id as twitch_user_table_id',
                'twitch_users.display_name'
            );

        // Wrap in an Eloquent builder using our dummy model
        $eloquentQuery = RaidersLeaderboardDummy::query()
            ->fromSub($query, 'raiders_leaderboard_data')
            ->selectRaw('raiders_leaderboard_data.*');

        // Apply default sort if no user sort is active
        // CRITICAL: Add secondary sort by user_id for stability
        if (! $this->hasSorts()) {
            $eloquentQuery->orderByDesc('raiders_leaderboard_data.raid_count')
                ->orderBy('raiders_leaderboard_data.user_id');
        } else {
            // When user sorts, we still need to add a stable secondary sort
            // This is handled by applySorting() automatically, but we need to ensure
            // user_id is always the final tiebreaker
        }

        return $eloquentQuery;
    }

    /**
     * Build rank cache based on current sort order
     */
    protected function buildRankCache(): void
    {
        $this->rankCache = [];

        // Build base query matching the main query structure
        $subquery = DB::table('raid_history')
            ->selectRaw('user_id, COUNT(*) as raid_count, SUM(viewers) as total_viewers')
            ->whereNotNull('user_id')
            ->groupBy('user_id');

        $query = DB::table(DB::raw("({$subquery->toSql()}) as raid_stats"))
            ->mergeBindings($subquery)
            ->join('twitch_users', 'raid_stats.user_id', '=', 'twitch_users.twitch_id')
            ->select(
                'raid_stats.user_id',
                'raid_stats.raid_count',
                'raid_stats.total_viewers',
                'twitch_users.display_name'
            );

        // Apply the same sorting as the main query
        $hasSorts = false;
        if ($this->hasSorts()) {
            $hasSorts = true;
            foreach ($this->getSorts() as $column => $direction) {
                // Map column names to the correct table prefix
                switch ($column) {
                    case 'user_id':
                        // Username sort
                        $query->orderByRaw('LOWER(twitch_users.display_name) '.$direction);
                        break;
                    case 'raid_count':
                        $query->orderBy('raid_count', $direction);
                        break;
                    case 'total_viewers':
                        $query->orderBy('total_viewers', $direction);
                        break;
                }
            }
        } else {
            // Default sort
            $query->orderByDesc('raid_count');
        }

        // CRITICAL: Always add user_id as final tiebreaker for stable sorting
        $query->orderBy('user_id');

        // Build rank map
        $rank = 1;
        foreach ($query->get() as $row) {
            $this->rankCache[(string) $row->user_id] = $rank++;
        }
    }

    /**
     * Define the columns for the table
     */
    public function columns(): array
    {
        return [
            $this->getRankColumn(),
            $this->getUsernameColumn(),
            $this->getRaidCountColumn(),
            $this->getTotalViewsColumn(),
        ];
    }

    /**
     * Get the rank column definition
     */
    protected function getRankColumn(): Column
    {
        return Column::make('Rank', 'user_id')
            ->format(fn ($value, $row, Column $column) => $this->rankCache[(string) $row->user_id] ?? '?')
            ->unclickable();
    }

    /**
     * Get the username column definition
     */
    protected function getUsernameColumn(): Column
    {
        return Column::make('Username', 'user_id')
            ->format(function ($value, $row, Column $column) {
                $twitchUser = TwitchUser::where('twitch_id', $row->user_id)
                    ->with('user')
                    ->first();

                if (! $twitchUser) {
                    return view('livewire.tables.user-link', [
                        'displayName' => 'Unknown',
                        'hasUser' => false,
                        'twitchId' => null,
                    ]);
                }

                return view('livewire.tables.user-link', [
                    'displayName' => $twitchUser->display_name ?? 'Unknown',
                    'hasUser' => $twitchUser->user !== null,
                    'twitchId' => $twitchUser->twitch_id ?? null,
                ]);
            })
            ->sortable(function (Builder $query, string $direction) {
                // Add secondary sort for stability
                return $query->orderByRaw('LOWER(raiders_leaderboard_data.display_name) '.$direction)
                    ->orderBy('raiders_leaderboard_data.user_id');
            })
            ->searchable(function (Builder $query, $searchTerm) {
                $dbDriver = $query->getConnection()->getDriverName();
                $operator = $dbDriver === 'pgsql' ? 'ilike' : 'like';

                return $query->where('raiders_leaderboard_data.display_name', $operator, "%{$searchTerm}%");
            });
    }

    /**
     * Get the raid count column definition
     */
    protected function getRaidCountColumn(): Column
    {
        return Column::make('Times Raided', 'raid_count')
            ->sortable(function (Builder $query, string $direction) {
                // Add secondary sort by user_id for stability
                return $query->orderBy('raiders_leaderboard_data.raid_count', $direction)
                    ->orderBy('raiders_leaderboard_data.user_id');
            })
            ->setSortingPillDirections('0-9', '9-0')
            ->format(fn ($value) => number_format((int) $value));
    }

    /**
     * Get the total views column definition
     */
    protected function getTotalViewsColumn(): Column
    {
        return Column::make('Total Views', 'total_viewers')
            ->sortable(function (Builder $query, string $direction) {
                // Add secondary sort by user_id for stability
                return $query->orderBy('raiders_leaderboard_data.total_viewers', $direction)
                    ->orderBy('raiders_leaderboard_data.user_id');
            })
            ->setSortingPillDirections('0-9', '9-0')
            ->format(fn ($value) => number_format((int) $value));
    }
}
